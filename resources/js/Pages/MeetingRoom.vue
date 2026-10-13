<script setup>
import { Head } from '@inertiajs/vue3';
import { Room, RoomEvent, Track } from 'livekit-client';
import { computed, nextTick, onBeforeUnmount, ref, shallowRef } from 'vue';

const props = defineProps({
    code: { type: String, required: true },
    meeting: { type: Object, required: true },
    isHost: { type: Boolean, default: false },
    canRecord: { type: Boolean, default: false },
});

const displayName = ref('');
const joined = ref(false);
const joining = ref(false);
const cameraOn = ref(false);
const micOn = ref(false);
const micBusy = ref(false);
const recording = ref(false);
const recordingStatus = ref('');
const elapsed = ref(0);
const copied = ref(false);
const waiting = ref(false);
const meetingEnded = ref(false);
const denied = ref(false);
const screenSharing = ref(false);
const waitingParticipants = ref([]);
const chatMessages = ref([]);
const chatDraft = ref('');
const chatError = ref('');
const lastChatId = ref(0);
const localVideo = ref(null);
const remoteContainer = ref(null);
const error = ref('');
const participantList = ref(props.meeting.participants || []);
const remoteVideoCount = ref(0);
// LiveKit Room is a complex class instance; a deep Vue ref proxies its internal
// camera constraints and causes structuredClone to fail before Chrome can prompt.
const lkRoom = shallowRef(null);
const startMillis = ref(props.meeting.started_at ? new Date(props.meeting.started_at).getTime() : null);
const inviteLink = computed(() => window.location.href);
const formattedTime = computed(() => {
    const h = Math.floor(elapsed.value / 3600);
    const m = Math.floor((elapsed.value % 3600) / 60);
    const s = elapsed.value % 60;
    return [h, m, s].map(v => String(v).padStart(2, '0')).join(':');
});
let timer;
let waitingTimer;
let meetingHealthTimer;
let endedRedirectTimer;
let checkingAdmission = false;
let checkingMeetingHealth = false;
let hostWaitingTimer;
let chatTimer;
let mediaRecorder;
let recordStartedAt = 0;
let recordChunks = [];
const remoteTiles = new Map();
const remoteTracks = new Map();

function finishMeeting() {
    clearInterval(waitingTimer);
    clearInterval(meetingHealthTimer);
    clearInterval(chatTimer);
    clearInterval(hostWaitingTimer);
    if (mediaRecorder?.state === 'recording') mediaRecorder.stop();
    recording.value = false;
    lkRoom.value?.disconnect();
    lkRoom.value = null;
    if (localVideo.value) localVideo.value.srcObject = null;
    joined.value = false;
    waiting.value = false;
    meetingEnded.value = true;
    cameraOn.value = false;
    micOn.value = false;
    screenSharing.value = false;
    participantList.value = [];
    if (!props.isHost) {
        endedRedirectTimer = setTimeout(() => window.location.replace('/'), 2500);
    }
}

const updateClock = () => {
    if (!startMillis.value) return;
    elapsed.value = Math.max(0, Math.floor((Date.now() - startMillis.value) / 1000));
    if (elapsed.value >= props.meeting.duration_limit_minutes * 60 && lkRoom.value?.state === 'connected') {
        if (props.isHost) endMeeting();
        else finishMeeting();
    }
};
timer = setInterval(updateClock, 1000);

const updateParticipant = participant => {
    const identity = participant.identity;
    const entry = {
        identity,
        display_name: participant.name || participantList.value.find(p => p.identity === identity)?.display_name || 'Participant',
        role: participantList.value.find(p => p.identity === identity)?.role || 'guest',
    };
    const index = participantList.value.findIndex(p => p.identity === identity);
    if (index === -1) participantList.value.push(entry);
    else participantList.value[index] = entry;
};
const ensureRemoteTile = participant => {
    const existing = remoteTiles.get(participant.identity);
    if (existing) return existing;
    if (!remoteContainer.value) return null;

    const tile = document.createElement('div');
    tile.dataset.participantIdentity = participant.identity;
    tile.className = 'relative aspect-video min-h-[220px] overflow-hidden rounded-2xl border border-white/10 bg-slate-900';
    const avatar = document.createElement('div');
    avatar.dataset.avatar = 'true';
    avatar.className = 'absolute inset-0 grid place-content-center text-center';
    const initial = document.createElement('span');
    initial.className = 'mx-auto grid h-16 w-16 place-items-center rounded-full bg-indigo-500 text-2xl font-bold text-white';
    initial.textContent = (participant.name || 'P').slice(0, 1).toUpperCase();
    avatar.appendChild(initial);
    const label = document.createElement('span');
    label.className = 'absolute bottom-3 left-3 rounded-lg bg-black/55 px-2.5 py-1 text-xs font-medium text-white';
    label.textContent = participant.name || participant.identity;
    tile.append(avatar, label);
    remoteContainer.value.appendChild(tile);

    const entry = { tile, avatar, videoCount: 0 };
    remoteTiles.set(participant.identity, entry);
    remoteVideoCount.value += 1;
    return entry;
};
const connectToRoom = async credentials => {
    remoteTiles.clear();
    remoteTracks.clear();
    remoteVideoCount.value = 0;
    const room = new Room({ adaptiveStream: true, dynacast: true });
    lkRoom.value = room;
    room.on(RoomEvent.ParticipantConnected, participant => {
        updateParticipant(participant);
        ensureRemoteTile(participant);
    });
    room.on(RoomEvent.ParticipantDisconnected, participant => {
        participantList.value = participantList.value.filter(p => p.identity !== participant.identity);
        const entry = remoteTiles.get(participant.identity);
        entry?.tile.remove();
        if (entry) remoteVideoCount.value = Math.max(0, remoteVideoCount.value - 1);
        remoteTiles.delete(participant.identity);
        for (const [track, trackEntry] of remoteTracks) {
            if (trackEntry.identity === participant.identity) remoteTracks.delete(track);
        }
    });
    room.on(RoomEvent.TrackSubscribed, (track, publication, participant) => {
        updateParticipant(participant);
        const entry = track.kind === Track.Kind.Video ? ensureRemoteTile(participant) : null;
        if (!entry) return;
        nextTick(() => {
            const element = track.attach();
            element.classList.add('absolute', 'inset-0', 'h-full', 'w-full', 'bg-slate-900', 'object-cover');
            entry.tile.insertBefore(element, entry.avatar);
            entry.avatar.style.display = 'none';
            entry.videoCount += 1;
            remoteTracks.set(track, { identity: participant.identity, entry });
        });
    });
    room.on(RoomEvent.TrackUnsubscribed, track => {
        track.detach().forEach(element => element.remove());
        const trackEntry = remoteTracks.get(track);
        if (trackEntry) {
            trackEntry.entry.videoCount = Math.max(0, trackEntry.entry.videoCount - 1);
            if (!trackEntry.entry.videoCount) trackEntry.entry.avatar.style.display = '';
            remoteTracks.delete(track);
        }
    });
    await room.connect(credentials.server_url, credentials.token);
    participantList.value = Array.from(room.remoteParticipants.values()).map(p => ({
        identity: p.identity,
        display_name: p.name || 'Participant',
        role: 'guest',
    }));
    room.remoteParticipants.forEach(participant => ensureRemoteTile(participant));
};

const enterCall = async credentials => {
    waiting.value = false;
    clearInterval(waitingTimer);
    participantList.value = credentials.participants || [];
    startMillis.value = new Date(credentials.meeting.started_at).getTime();
    await nextTick();
    joined.value = true;
    await nextTick();
    await connectToRoom(credentials);
    updateClock();
    await loadMessages();
    chatTimer = setInterval(loadMessages, 2000);
    if (props.isHost) {
        await loadWaitingParticipants();
        hostWaitingTimer = setInterval(loadWaitingParticipants, 2000);
    } else {
        meetingHealthTimer = setInterval(checkMeetingHealth, 2000);
    }
};
const checkAdmission = async () => {
    if (!waiting.value || checkingAdmission) return;
    checkingAdmission = true;
    try {
        const response = await window.axios.get(route('meeting.status', { code: props.code }));
        if (response.data.state === 'ended') {
            finishMeeting();
            return;
        }
        if (response.data.state === 'denied') {
            clearInterval(waitingTimer);
            waiting.value = false;
            denied.value = true;
            return;
        }
        if (response.data.state === 'admitted' && response.data.token) {
            try {
                await enterCall(response.data);
            } catch (e) {
                clearInterval(waitingTimer);
                lkRoom.value?.disconnect();
                lkRoom.value = null;
                joined.value = false;
                waiting.value = false;
                error.value = e.message || 'You were admitted, but the meeting connection failed. Please join again.';
            }
        }
    } catch (e) {
        if (e.response?.status === 403 || e.response?.status === 404 || e.response?.status === 422) {
            clearInterval(waitingTimer);
            waiting.value = false;
            error.value = e.response?.data?.message || 'Could not confirm your waiting room session. Please join the meeting again.';
        }
    } finally {
        checkingAdmission = false;
    }
};
const checkMeetingHealth = async () => {
    if (!joined.value || checkingMeetingHealth) return;
    checkingMeetingHealth = true;
    try {
        const response = await window.axios.get(route('meeting.health', { code: props.code }));
        if (response.data.state === 'ended') finishMeeting();
    } catch {
        // Keep the call up during a temporary status request failure.
    } finally {
        checkingMeetingHealth = false;
    }
};
const loadWaitingParticipants = async () => {
    try {
        const response = await window.axios.get(route('meeting.waiting', { code: props.code }));
        waitingParticipants.value = response.data.participants;
    } catch {}
};
const decideParticipant = async (participant, decision) => {
    try {
        await window.axios.post(route('meeting.participants.decision', { code: props.code, participant: participant.id }), { decision });
        await loadWaitingParticipants();
    } catch (e) {
        error.value = e.response?.data?.message || 'Could not update the waiting room.';
    }
};
const loadMessages = async () => {
    if (!joined.value) return;
    try {
        const response = await window.axios.get(route('meeting.chat.index', { code: props.code, after: lastChatId.value }));
        chatMessages.value.push(...response.data.messages);
        if (response.data.messages.length) lastChatId.value = response.data.messages[response.data.messages.length - 1].id;
    } catch {}
};
const sendMessage = async () => {
    const body = chatDraft.value.trim();
    if (!body) return;
    chatError.value = '';
    try {
        const response = await window.axios.post(route('meeting.chat.store', { code: props.code }), { body });
        chatMessages.value.push(response.data.message);
        lastChatId.value = response.data.message.id;
        chatDraft.value = '';
    } catch (e) {
        chatError.value = e.response?.data?.message || 'Message could not be sent.';
    }
};
const toggleScreenShare = async () => {
    if (!lkRoom.value) return;
    try {
        await lkRoom.value.localParticipant.setScreenShareEnabled(!screenSharing.value);
        screenSharing.value = !screenSharing.value;
    } catch {
        screenSharing.value = false;
        error.value = 'Screen sharing was cancelled or is not supported by this browser.';
    }
};
const joinRoom = async () => {
    if (!displayName.value.trim()) return;
    joining.value = true;
    error.value = '';
    try {
        const response = await window.axios.post(route('meeting.join', { code: props.code }), {
            display_name: displayName.value.trim(),
        });
        if (response.data.waiting) {
            waiting.value = true;
            waitingTimer = setInterval(checkAdmission, 1800);
            return;
        }
        await enterCall(response.data);
    } catch (e) {
        joined.value = false;
        error.value = e.response?.data?.errors?.meeting?.[0]
            || e.response?.data?.errors?.display_name?.[0]
            || e.response?.data?.message
            || 'Could not join this meeting. Check your connection and LiveKit configuration.';
    } finally {
        joining.value = false;
    }
};

const toggleCamera = async () => {
    if (!lkRoom.value) return;
    const enabling = !cameraOn.value;
    error.value = '';
    try {
        if (enabling && !navigator.mediaDevices?.getUserMedia) {
            throw Object.assign(new Error('Camera access is unavailable on this page.'), { name: 'SecurityError' });
        }
        if (enabling && navigator.permissions?.query) {
            try {
                const permission = await navigator.permissions.query({ name: 'camera' });
                if (permission.state === 'denied') {
                    throw Object.assign(new Error('Chrome reports camera permission is blocked for this site.'), { name: 'NotAllowedError' });
                }
            } catch (permissionError) {
                if (permissionError.name === 'NotAllowedError') throw permissionError;
                // Some browsers do not support querying camera permission; the capture request below still works.
            }
        }
        const publication = await lkRoom.value.localParticipant.setCameraEnabled(enabling);
        cameraOn.value = enabling;
        if (enabling && publication?.track && localVideo.value) {
            publication.track.attach(localVideo.value);
        } else if (localVideo.value) {
            localVideo.value.srcObject = null;
        }
    } catch (e) {
        cameraOn.value = false;
        const name = e?.name || '';
        const details = e?.message ? ` (${e.message})` : '';
        if (name === 'NotAllowedError' || name === 'PermissionDeniedError' || name === 'SecurityError') {
            error.value = `Camera permission is blocked (${name}). For this page, open the site controls beside the address bar and reset Camera permission, then reload. If Chrome still does not ask, allow camera access for desktop apps in Windows Settings > Privacy & security > Camera. The browser or device may also be managed by policy.` + details;
        } else if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
            error.value = 'No camera was found. Connect a camera and check Windows camera settings.' + details;
        } else if (name === 'NotReadableError' || name === 'TrackStartError') {
            error.value = 'The camera is busy or blocked by Windows. Close apps using it and try again.' + details;
        } else {
            error.value = 'Could not start the camera. Check Chrome and Windows camera access.' + details;
        }
    }
};
const toggleMic = async () => {
    if (!lkRoom.value || micBusy.value) return;
    const enabling = !micOn.value;
    micBusy.value = true;
    error.value = '';
    try {
        if (enabling) {
            if (!navigator.mediaDevices?.getUserMedia) {
                throw Object.assign(new Error('Microphone access is unavailable on this page.'), { name: 'SecurityError' });
            }
            if (navigator.permissions?.query) {
                try {
                    const permission = await navigator.permissions.query({ name: 'microphone' });
                    if (permission.state === 'denied') {
                        throw Object.assign(new Error('Microphone permission is blocked for this site.'), { name: 'NotAllowedError' });
                    }
                } catch (permissionError) {
                    if (permissionError.name === 'NotAllowedError') throw permissionError;
                }
            }
        }
        await lkRoom.value.localParticipant.setMicrophoneEnabled(enabling);
        micOn.value = enabling;
    } catch (e) {
        micOn.value = false;
        const name = e?.name || '';
        const details = e?.message ? ` (${e.message})` : '';
        if (name === 'NotAllowedError' || name === 'PermissionDeniedError' || name === 'SecurityError') {
            error.value = 'Microphone permission is blocked. Allow Microphone for localhost:5174 in Chrome site settings, then try again.' + details;
        } else if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
            error.value = 'No microphone was found. Connect a microphone and select it in Windows sound settings.' + details;
        } else if (name === 'NotReadableError' || name === 'TrackStartError') {
            error.value = 'The microphone is busy or blocked by Windows. Close apps using it and try again.' + details;
        } else {
            error.value = 'Could not start the microphone. Check Chrome and Windows microphone access.' + details;
        }
    } finally {
        micBusy.value = false;
    }
};

const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(inviteLink.value);
        copied.value = true;
        setTimeout(() => copied.value = false, 1800);
    } catch {
        error.value = 'Could not copy the invitation link. Copy it from the address bar.';
    }
};

const startRecording = () => {
    if (!props.canRecord) {
        recordingStatus.value = 'Recording is unavailable on this plan or for this account.';
        return;
    }
    if (!window.confirm('Confirm everyone in the meeting has agreed to recording. The local recording will be uploaded privately.')) return;
    const participant = lkRoom.value?.localParticipant;
    const tracks = participant ? [
        ...participant.videoTrackPublications.values(),
        ...participant.audioTrackPublications.values(),
    ].map(publication => publication.track?.mediaStreamTrack).filter(Boolean) : [];
    if (!tracks.length) {
        recordingStatus.value = 'Turn on your camera or microphone before recording.';
        return;
    }
    try {
        const mediaStream = new MediaStream(tracks);
        const mimeType = MediaRecorder.isTypeSupported('video/webm;codecs=vp9,opus')
            ? 'video/webm;codecs=vp9,opus' : 'video/webm';
        mediaRecorder = new MediaRecorder(mediaStream, { mimeType });
        recordChunks = [];
        recordStartedAt = Date.now();
        mediaRecorder.ondataavailable = event => { if (event.data.size) recordChunks.push(event.data); };
        mediaRecorder.onstop = async () => {
            const form = new FormData();
            form.append('recording', new Blob(recordChunks, { type: mediaRecorder.mimeType || 'video/webm' }), 'meetly-' + props.code + '.webm');
            form.append('duration_seconds', String(Math.max(1, Math.ceil((Date.now() - recordStartedAt) / 1000))));
            recordingStatus.value = 'Uploading recording securely...';
            try {
                const result = await window.axios.post(route('meeting.recordings.store', { code: props.code }), form);
                recordingStatus.value = result.data.message;
            } catch (e) {
                recordingStatus.value = e.response?.data?.message || 'Recording upload failed.';
            }
            recordChunks = [];
        };
        mediaRecorder.start(1000);
        recording.value = true;
        recordingStatus.value = 'Recording this device camera and microphone.';
    } catch {
        recordingStatus.value = 'This browser does not support recording.';
    }
};
const stopRecording = () => {
    if (mediaRecorder?.state === 'recording') mediaRecorder.stop();
    recording.value = false;
};

const leaveRoom = async (message = '') => {
    if (mediaRecorder?.state === 'recording') stopRecording();
    try { await window.axios.post(route('meeting.leave', { code: props.code })); } catch {}
    lkRoom.value?.disconnect();
    if (message) error.value = message;
    joined.value = false;
};
const endMeeting = async () => {
    try {
        await window.axios.post(route('meeting.end', { code: props.code }));
        lkRoom.value?.disconnect();
        joined.value = false;
        window.location.href = route('dashboard');
    } catch (e) {
        error.value = e.response?.data?.message || 'Only the meeting host can end this meeting.';
    }
};
onBeforeUnmount(() => {
    clearInterval(timer);
    clearInterval(waitingTimer);
    clearInterval(meetingHealthTimer);
    clearTimeout(endedRedirectTimer);
    if (mediaRecorder?.state === 'recording') mediaRecorder.stop();
    lkRoom.value?.disconnect();
});
</script>

<template>
<Head :title="meeting.title || 'Meeting room'"/>
<div class="min-h-screen overflow-x-hidden bg-slate-950 text-white">
<header class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-4 py-4 sm:px-7"><a :href="route('dashboard')" class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-xl bg-indigo-500 font-bold">m</span><span class="font-semibold">meetly</span></a><div class="flex items-center gap-3 text-sm"><span class="rounded-lg bg-white/10 px-3 py-2 font-mono">{{formattedTime}} / {{meeting.duration_limit_minutes}} min</span><span class="rounded-full bg-emerald-400/10 px-3 py-1.5 text-emerald-300">{{meeting.title}} - {{code}}</span></div></header>
<main class="mx-auto grid min-h-[calc(100vh-5.5rem)] w-full max-w-7xl content-center gap-5 px-4 py-6 lg:grid-cols-[minmax(0,1fr)_300px]">
<section class="min-w-0 space-y-4">
<div v-if="meetingEnded" class="mx-auto mt-10 max-w-md rounded-3xl border border-white/10 bg-white/5 p-8 text-center"><h1 class="text-2xl font-bold">Meeting ended</h1><p class="mt-2 text-sm text-slate-400">The host ended this meeting. Returning to home...</p></div><div v-else-if="waiting" class="mx-auto mt-10 max-w-md rounded-3xl border border-white/10 bg-white/5 p-8 text-center"><div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-amber-400/10 text-amber-300"><svg aria-hidden="true" viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div><h1 class="mt-5 text-2xl font-bold">Waiting for host approval</h1><p class="mt-2 text-sm text-slate-400">You are in the waiting room. This page will connect when the host admits you.</p></div><div v-else-if="denied" class="mx-auto mt-10 max-w-md rounded-3xl border border-white/10 bg-white/5 p-8 text-center"><h1 class="text-2xl font-bold">The host did not admit you</h1></div><div v-else-if="!joined" class="mx-auto mt-10 max-w-md rounded-3xl border border-white/10 bg-white/5 p-7 text-center"><div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-indigo-500/20 text-white"><svg aria-hidden="true" viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="13" height="12" rx="2"/><path d="m16 10 5-3v10l-5-3z"/></svg></div><h1 class="mt-5 text-2xl font-bold">Join {{meeting.title}}</h1><p class="mt-2 text-sm text-slate-400">Enter your name. Anyone with this invitation link can join until the room ends or fills.</p><form class="mt-6 space-y-3" @submit.prevent="joinRoom"><input v-model="displayName" required maxlength="80" placeholder="Your display name" class="w-full rounded-xl border-white/10 bg-slate-900 text-white placeholder:text-slate-500 focus:border-indigo-400 focus:ring-indigo-400"/><button :disabled="joining" class="w-full rounded-xl bg-indigo-500 px-4 py-3 font-semibold hover:bg-indigo-400 disabled:opacity-50">{{joining?'Connecting...':'Join meeting'}}</button></form></div>
<template v-else><div ref="remoteContainer" class="grid grid-cols-1 gap-3" :class="remoteVideoCount > 0 ? 'sm:grid-cols-2' : ''"><div class="relative aspect-video min-h-[220px] overflow-hidden rounded-2xl border border-white/10 bg-slate-900"><video ref="localVideo" autoplay muted playsinline class="absolute inset-0 h-full w-full object-cover"></video><div v-if="!cameraOn" class="pointer-events-none absolute inset-0 grid place-content-center text-center"><div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-indigo-500 text-2xl font-bold">{{displayName.slice(0,1).toUpperCase()}}</div><p class="mt-3 font-medium">{{displayName}} - Camera off</p></div><span v-else class="absolute bottom-3 left-3 rounded-lg bg-black/55 px-2.5 py-1 text-xs font-medium">{{displayName}}</span></div></div><div class="flex flex-wrap items-center justify-center gap-3">
<button @click="toggleMic" :disabled="micBusy" class="inline-flex items-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold disabled:cursor-wait disabled:opacity-60" :class="micOn?'bg-white/10 hover:bg-white/20':'bg-rose-600 hover:bg-rose-500'"><svg v-if="micOn" aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10v2a7 7 0 0 0 14 0v-2M12 19v3m-4 0h8"/></svg><svg v-else aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 9v5a3 3 0 0 0 5.1 2.1M15 9V5a3 3 0 0 0-5.8-1M5 10v2a7 7 0 0 0 11.9 5M19 10v2a7 7 0 0 1-.7 3M12 19v3m-4 0h8M3 3l18 18"/></svg>{{micBusy?'Connecting...':micOn?'Mute':'Unmute'}}</button>
<button @click="toggleCamera" class="inline-flex items-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold" :class="cameraOn?'bg-white/10 hover:bg-white/20':'bg-rose-600 hover:bg-rose-500'"><svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="13" height="12" rx="2"/><path d="m16 10 5-3v10l-5-3z"/></svg>{{cameraOn?'Camera off':'Camera on'}}</button>
<button @click="toggleScreenShare" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-5 py-3 text-sm font-semibold hover:bg-white/20"><svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8m-4-4v4m0-13v7m-3-4 3-3 3 3"/></svg>{{screenSharing?'Stop sharing':'Share screen'}}</button>
<button v-if="isHost" @click="recording?stopRecording():startRecording()" class="inline-flex items-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold" :class="recording?'bg-rose-600':'bg-white/10 hover:bg-white/20'"><svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3" fill="currentColor" stroke="none"/></svg>{{recording?'Stop recording':'Record'}}</button>
<button @click="leaveRoom()" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-5 py-3 text-sm font-semibold hover:bg-white/20"><svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 17l5-5-5-5m5 5H3m10-9h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>Leave</button>
<button v-if="isHost" @click="endMeeting" class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-5 py-3 text-sm font-semibold hover:bg-rose-500"><svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v9m-6.4-6.4a9 9 0 1 0 12.8 0"/></svg>End for everyone</button>
</div><p v-if="recordingStatus" class="text-center text-sm text-slate-400">{{recordingStatus}}</p></template>
<p v-if="error" class="rounded-xl bg-rose-500/10 p-3 text-sm text-rose-300">{{error}}</p>
</section>
<aside class="space-y-4">
<section class="rounded-2xl border border-white/10 bg-white/5 p-5"><h2 class="font-semibold">Invite people</h2><p class="mt-2 text-sm leading-6 text-slate-400">Anyone with this link can join. The server enforces the participant limit.</p><div class="mt-4 flex gap-2"><input readonly :value="inviteLink" class="min-w-0 flex-1 rounded-lg border-white/10 bg-slate-900 text-xs text-slate-300"/><button @click="copyLink" class="rounded-lg bg-indigo-500 px-3 text-xs font-semibold">{{copied?'Copied':'Copy'}}</button></div></section>
<section class="rounded-2xl border border-white/10 bg-white/5 p-5"><div class="flex items-center justify-between"><h2 class="font-semibold">Participants</h2><span class="rounded-full bg-white/10 px-2 py-1 text-xs">{{participantList.length}}</span></div><p v-for="participant in participantList" :key="participant.identity" class="mt-3 text-sm text-slate-400">{{participant.display_name}} <span v-if="participant.role==='host'" class="text-indigo-300">- host</span></p></section>
<section v-if="isHost && waitingParticipants.length" class="rounded-2xl border border-amber-300/20 bg-amber-300/5 p-5"><h2 class="font-semibold">Waiting room - {{waitingParticipants.length}}</h2><div v-for="person in waitingParticipants" :key="person.id" class="mt-3 flex items-center justify-between gap-2 text-sm"><span class="truncate text-slate-300">{{person.display_name}}</span><div class="flex gap-2"><button @click="decideParticipant(person,'admit')" class="rounded-lg bg-emerald-500/20 px-2 py-1 text-emerald-300">Admit</button><button @click="decideParticipant(person,'deny')" class="rounded-lg bg-rose-500/20 px-2 py-1 text-rose-300">Deny</button></div></div></section><section v-if="joined" class="rounded-2xl border border-white/10 bg-white/5 p-5"><h2 class="font-semibold">Meeting chat</h2><div class="mt-3 max-h-56 space-y-3 overflow-y-auto"><p v-if="!chatMessages.length" class="text-xs text-slate-500">No messages yet.</p><div v-for="message in chatMessages" :key="message.id" class="rounded-lg bg-white/5 p-2"><p class="text-xs font-semibold text-indigo-300">{{message.name}}</p><p class="mt-1 break-words text-sm text-slate-200">{{message.body}}</p></div></div><form class="mt-3 flex gap-2" @submit.prevent="sendMessage"><input v-model="chatDraft" maxlength="2000" placeholder="Write a message..." class="min-w-0 flex-1 rounded-lg border-white/10 bg-slate-900 text-sm text-white"/><button class="rounded-lg bg-indigo-500 px-3 text-sm font-semibold">Send</button></form><p v-if="chatError" class="mt-2 text-xs text-rose-300">{{chatError}}</p></section><section class="rounded-2xl border border-white/10 bg-white/5 p-5"><h2 class="font-semibold">Recording</h2><p class="mt-2 text-sm leading-6 text-slate-400">Host recordings are uploaded to private Laravel storage and checked against the plan limit.</p></section>
</aside>
</main></div>
</template>
