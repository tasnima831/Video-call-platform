<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Services\LiveKitTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MeetingAccessController extends Controller
{
    public function status(Request $request, string $code, LiveKitTokenService $tokens): JsonResponse
    {
        $meeting = Meeting::where('code', strtoupper($code))->firstOrFail();
        $participantId = $request->session()->get('meeting_participant_'.$meeting->id);
        $participant = $participantId
            ? MeetingParticipant::where('meeting_id', $meeting->id)->find($participantId)
            : null;
        abort_unless($participant, 403);

        if ($participant->state === 'denied' || $participant->left_at) {
            return response()->json(['state' => 'denied']);
        }
        if ($participant->state !== 'admitted') {
            return response()->json(['state' => 'waiting']);
        }
        if ($request->session()->get('meeting_token_issued_'.$meeting->id)) {
            return response()->json(['state' => 'admitted', 'token_issued' => true]);
        }
        if ($meeting->status === 'ended' || !$meeting->started_at) {
            throw ValidationException::withMessages(['meeting' => 'The host has not started this meeting yet.']);
        }

        $remaining = max(1, (int) now()->diffInSeconds($meeting->started_at->copy()->addMinutes($meeting->duration_limit_minutes), false));
        try {
            $credentials = $tokens->create($meeting, $participant, $remaining);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['meeting' => $exception->getMessage()]);
        }
        $request->session()->put('meeting_token_issued_'.$meeting->id, true);

        return response()->json([
            'state' => 'admitted',
            'participant' => ['identity' => $participant->identity, 'display_name' => $participant->display_name, 'role' => $participant->role],
            'meeting' => ['title' => $meeting->title, 'started_at' => $meeting->started_at->toIso8601String()],
            'participants' => $meeting->participants()->whereNull('left_at')->where('state', 'admitted')->get(['identity', 'display_name', 'role']),
            ...$credentials,
        ]);
    }

    public function waiting(Request $request, string $code): JsonResponse
    {
        $meeting = Meeting::where('code', strtoupper($code))->firstOrFail();
        abort_unless($request->user()?->id === $meeting->host_user_id, 403);

        return response()->json(['participants' => $meeting->participants()
            ->whereNull('left_at')->where('state', 'waiting')
            ->get(['id', 'identity', 'display_name', 'created_at'])]);
    }

    public function decide(Request $request, string $code, MeetingParticipant $participant): JsonResponse
    {
        $meeting = Meeting::where('code', strtoupper($code))->firstOrFail();
        abort_unless($request->user()?->id === $meeting->host_user_id, 403);
        abort_unless($participant->meeting_id === $meeting->id && $participant->state === 'waiting', 404);
        $data = $request->validate(['decision' => ['required', 'in:admit,deny']]);

        $participant->update([
            'state' => $data['decision'] === 'admit' ? 'admitted' : 'denied',
            'joined_at' => $data['decision'] === 'admit' ? now() : null,
            'left_at' => $data['decision'] === 'deny' ? now() : null,
        ]);

        return response()->json(['ok' => true]);
    }
}