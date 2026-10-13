<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\Plan;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\LiveKitTokenService;
use App\Services\PlanManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class MeetingController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;
        $meetings = Meeting::query()
            ->where('host_user_id', $userId)
            ->orWhereHas('participants', fn ($query) => $query->where('user_id', $userId))
            ->withCount(['participants as active_participants_count' => fn ($query) => $query->whereNull('left_at')])
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (Meeting $meeting) => [
                'code' => $meeting->code,
                'title' => $meeting->title,
                'date' => $meeting->started_at?->toIso8601String() ?? $meeting->created_at->toIso8601String(),
                'status' => $meeting->status,
                'participants' => $meeting->active_participants_count,
                'url' => route('meeting.show', $meeting->code),
                'is_host' => $meeting->host_user_id === $userId,
            ]);

        return Inertia::render('Meetings', ['meetings' => $meetings]);
    }

    public function store(Request $request, PlanManager $plans): RedirectResponse
    {
        $data = $request->validate(['title' => ['nullable', 'string', 'max:120']]);
        $plan = $plans->current($request->user());
        $code = Str::upper(Str::random(10));

        $meeting = Meeting::create([
            'host_user_id' => $request->user()->id,
            'code' => $code,
            'livekit_room' => 'meetly-'.$code,
            'title' => $data['title'] ?? 'Instant meeting',
            'status' => 'scheduled',
            'duration_limit_minutes' => $plan->max_duration_minutes,
            'participant_limit' => $plan->max_participants,
        ]);

        return redirect()->route('meeting.show', $meeting->code);
    }

    public function show(Request $request, string $code, PlanManager $plans): Response
    {
        $meeting = Meeting::where('code', strtoupper($code))->firstOrFail();
        $this->expireIfNeeded($meeting);

        $isHost = $request->user()?->id === $meeting->host_user_id;
        $canRecord = false;
        if ($isHost) {
            $plan = $plans->current($request->user());
            $used = (int) UsageRecord::where('user_id', $request->user()->id)->where('type', 'recording_minutes')->where('period_start', now()->startOfMonth()->toDateString())->sum('quantity');
            $canRecord = $used < $plan->monthly_recording_minutes;
        }

        return Inertia::render('MeetingRoom', [
            'code' => $meeting->code,
            'isHost' => $isHost,
            'canRecord' => $canRecord,
            'meeting' => [
                'title' => $meeting->title,
                'status' => $meeting->status,
                'started_at' => $meeting->started_at?->toIso8601String(),
                'duration_limit_minutes' => $meeting->duration_limit_minutes,
                'participants' => $meeting->participants()->whereNull('left_at')->where('state', 'admitted')->get(['identity', 'display_name', 'role']),
            ],
        ]);
    }

    public function join(Request $request, string $code, PlanManager $plans, LiveKitTokenService $tokens): JsonResponse
    {
        $meeting = Meeting::where('code', strtoupper($code))->firstOrFail();
        $this->expireIfNeeded($meeting);

        if ($meeting->status === 'ended') {
            throw ValidationException::withMessages(['meeting' => 'This meeting has ended.']);
        }

        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:80'],
            'password' => ['nullable', 'string', 'max:100'],
        ]);

        if ($meeting->password_hash && !Hash::check($data['password'] ?? '', $meeting->password_hash)) {
            throw ValidationException::withMessages(['password' => 'The meeting password is incorrect.']);
        }

        $activeCount = $meeting->participants()->whereNull('left_at')->count();
        if ($activeCount >= $meeting->participant_limit) {
            throw ValidationException::withMessages(['meeting' => 'This meeting has reached its participant limit.']);
        }

        $role = $request->user()?->id === $meeting->host_user_id ? 'host' : 'guest';
        $participant = MeetingParticipant::create([
            'meeting_id' => $meeting->id,
            'user_id' => $request->user()?->id,
            'identity' => (string) Str::uuid(),
            'display_name' => trim($data['display_name']),
            'role' => $role,
            'state' => $role === 'host' ? 'admitted' : 'waiting',
            'joined_at' => $role === 'host' ? now() : null,
        ]);

        $request->session()->put('meeting_participant_'.$meeting->id, $participant->id);
        if ($role !== 'host') {
            return response()->json(['waiting' => true, 'message' => 'Waiting for the host to admit you.']);
        }

        if (!$meeting->started_at) {
            $meeting->update(['started_at' => now(), 'status' => 'active']);
            $meeting->refresh();
        }

        $remaining = max(1, (int) ($meeting->started_at->copy()->addMinutes($meeting->duration_limit_minutes)->diffInSeconds(now(), false) * -1));
        try {
            $credentials = $tokens->create($meeting, $participant, $remaining);
        } catch (RuntimeException $exception) {
            $participant->update(['left_at' => now()]);
            throw ValidationException::withMessages(['meeting' => $exception->getMessage()]);
        }

        $request->session()->put('meeting_participant_'.$meeting->id, $participant->id);

        return response()->json([
            'participant' => ['identity' => $participant->identity, 'display_name' => $participant->display_name, 'role' => $participant->role],
            'meeting' => ['title' => $meeting->title, 'started_at' => $meeting->started_at->toIso8601String()],
            'participants' => $meeting->participants()->whereNull('left_at')->where('state', 'admitted')->get(['identity', 'display_name', 'role']),
            ...$credentials,
        ]);
    }

    public function leave(Request $request, string $code): JsonResponse
    {
        $meeting = Meeting::where('code', strtoupper($code))->firstOrFail();
        $participantId = $request->session()->pull('meeting_participant_'.$meeting->id);
        $participant = $participantId ? MeetingParticipant::where('meeting_id', $meeting->id)->find($participantId) : null;

        if ($participant && !$participant->left_at) {
            $participant->update(['left_at' => now()]);
            if ($participant->user_id && $participant->joined_at) {
                UsageRecord::create([
                    'user_id' => $participant->user_id,
                    'meeting_id' => $meeting->id,
                    'type' => 'participant_minutes',
                    'quantity' => round($participant->joined_at->diffInSeconds(now()) / 60, 2),
                    'period_start' => now()->startOfMonth()->toDateString(),
                ]);
            }
        }

        return response()->json(['ok' => true]);
    }

    public function end(Request $request, string $code): JsonResponse
    {
        $meeting = Meeting::where('code', strtoupper($code))->firstOrFail();
        abort_unless($request->user()?->id === $meeting->host_user_id, 403);

        if ($meeting->status !== 'ended') {
            $meeting->update(['status' => 'ended', 'ended_at' => now()]);
            $meeting->participants()->whereNull('left_at')->update(['left_at' => now()]);
            try {
                (new \Agence104\LiveKit\RoomServiceClient(config('services.livekit.url'), config('services.livekit.api_key'), config('services.livekit.api_secret')))->deleteRoom($meeting->livekit_room);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return response()->json(['ok' => true]);
    }

    private function expireIfNeeded(Meeting $meeting): void
    {
        if ($meeting->status !== 'ended' && $meeting->started_at &&
            $meeting->started_at->addMinutes($meeting->duration_limit_minutes)->isPast()) {
            $meeting->update(['status' => 'ended', 'ended_at' => now()]);
            $meeting->participants()->whereNull('left_at')->update(['left_at' => now()]);
        }
    }
}


