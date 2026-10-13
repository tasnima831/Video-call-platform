<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import WorkspaceSidebar from '@/Components/WorkspaceSidebar.vue';
import { ref } from 'vue';

const meetingCode = ref('');
const createMeeting = () => router.post(route('meetings.store'), {});
const joinMeeting = () => {
    if (!meetingCode.value.trim()) return alert('Enter a meeting code to continue.');
    const code = meetingCode.value.trim().split('/').filter(Boolean).pop();
    window.location.href = route('meeting.show', { code });
};

const meetings = [
    { title: 'Product design sync', date: 'Today', time: '10:30 AM', people: '4 people', color: 'bg-violet-100 text-violet-700', icon: '✳' },
    { title: 'Weekly team check-in', date: 'Today', time: '2:00 PM', people: '8 people', color: 'bg-sky-100 text-sky-700', icon: '◷' },
    { title: 'Project kickoff', date: 'Tomorrow', time: '11:00 AM', people: '6 people', color: 'bg-amber-100 text-amber-700', icon: '↗' },
];
const tools = [
    { title: 'Meeting history', route: 'meetings.index', description: 'Revisit calls and shared details.', icon: '◷', color: 'bg-indigo-50 text-indigo-600' },
    { title: 'My notes', route: 'notes.index', description: 'Your notes and action items in one place.', icon: '✎', color: 'bg-emerald-50 text-emerald-600' },
    { title: 'AI summaries', route: 'summaries.index', description: 'Quick recaps from meeting transcripts.', icon: '✧', color: 'bg-fuchsia-50 text-fuchsia-600' },
];
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#f6f7fb]">
            <div class="mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
                <div class="grid gap-8 lg:grid-cols-[230px_minmax(0,1fr)]">
                    <WorkspaceSidebar />

                    <main class="min-w-0 space-y-8">
                        <header class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-400">Monday, October 13</p>
                                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Good morning, {{ $page.props.auth.user.name }} <span>✦</span></h1>
                                <p class="mt-2 text-sm text-slate-500">Ready to bring your team together?</p>
                            </div>
                            <div class="hidden h-11 w-11 items-center justify-center rounded-full bg-white text-lg shadow-sm ring-1 ring-slate-200 sm:flex">🔔</div>
                        </header>

                        <section class="grid gap-5 md:grid-cols-2">
                            <article class="relative overflow-hidden rounded-3xl bg-indigo-600 p-7 text-white shadow-xl shadow-indigo-200/60 sm:p-8">
                                <div class="absolute -right-10 -top-12 h-48 w-48 rounded-full border-[28px] border-white/10"></div>
                                <div class="relative">
                                    <span class="grid h-11 w-11 place-items-center rounded-2xl bg-white/15 text-xl">▣</span>
                                    <h2 class="mt-5 text-xl font-bold">Start a new meeting</h2>
                                    <p class="mt-2 max-w-xs text-sm leading-6 text-indigo-100">Create a room instantly and invite people with a shareable link.</p>
                                    <button type="button" @click="createMeeting" class="mt-6 rounded-xl bg-white px-5 py-3 text-sm font-bold text-indigo-700 transition hover:bg-indigo-50">＋&nbsp; New meeting</button>
                                </div>
                            </article>
                            <article class="rounded-3xl border border-slate-200/80 bg-white p-7 shadow-sm sm:p-8">
                                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-slate-100 text-xl text-slate-700">↗</span>
                                <h2 class="mt-5 text-xl font-bold text-slate-900">Join a meeting</h2>
                                <p class="mt-2 text-sm leading-6 text-slate-500">Paste an invitation code to join a call.</p>
                                <form class="mt-5 flex gap-2" @submit.prevent="joinMeeting">
                                    <input v-model="meetingCode" aria-label="Meeting invitation code" placeholder="Enter meeting code" class="min-w-0 flex-1 rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    <button type="submit" class="rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-700">Join</button>
                                </form>
                            </article>
                        </section>

                        <section>
                            <div class="mb-4 flex items-end justify-between">
                                <div><h2 class="text-lg font-bold text-slate-900">Upcoming meetings</h2><p class="mt-1 text-sm text-slate-500">Your next calls at a glance.</p></div>
                                <a href="#" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">View calendar <span aria-hidden="true">→</span></a>
                            </div>
                            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                                <div v-for="(meeting, index) in meetings" :key="meeting.title" class="flex items-center gap-4 px-4 py-4 sm:px-5" :class="{ 'border-t border-slate-100': index > 0 }">
                                    <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl text-lg" :class="meeting.color">{{ meeting.icon }}</div>
                                    <div class="min-w-0 flex-1"><h3 class="truncate text-sm font-semibold text-slate-800">{{ meeting.title }}</h3><p class="mt-1 text-xs text-slate-400">{{ meeting.date }} · {{ meeting.time }} <span class="px-1">·</span> {{ meeting.people }}</p></div>
                                    <button class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-indigo-200 hover:text-indigo-700">Details</button>
                                </div>
                                <div class="border-t border-slate-100 bg-slate-50/70 px-5 py-3 text-xs text-slate-400">Sample schedule · Calendar integration is coming soon</div>
                            </div>
                        </section>

                        <section>
                            <div class="mb-4"><h2 class="text-lg font-bold text-slate-900">Your workspace</h2><p class="mt-1 text-sm text-slate-500">Everything you need before and after a call.</p></div>
                            <div class="grid gap-4 sm:grid-cols-3">
                                <Link v-for="tool in tools" :key="tool.title" :href="route(tool.route)" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                    <span class="grid h-10 w-10 place-items-center rounded-xl text-lg" :class="tool.color">{{ tool.icon }}</span>
                                    <h3 class="mt-4 text-sm font-bold text-slate-800">{{ tool.title }}</h3>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ tool.description }}</p>
                                    <span class="mt-4 inline-block text-xs font-semibold text-indigo-600">Open page →</span>
                                </Link>
                            </div>
                        </section>

                        <footer class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 pt-5 text-xs text-slate-400"><span>Meetly · Video calls made simple</span><span>Meetings are protected by your workspace access</span></footer>
                    </main>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>







