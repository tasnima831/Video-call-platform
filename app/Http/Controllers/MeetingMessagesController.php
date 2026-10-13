<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingMessage;
use App\Models\MeetingParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeetingMessagesController extends Controller
{
    public function index(Request $request, string $code): JsonResponse
    {
        [$meeting, $participant] = $this->participant($request, $code);
        $after = max(0, (int) $request->query('after', 0));
        $messages = MeetingMessage::with('participant:id,display_name')
            ->where('meeting_id', $meeting->id)->where('id', '>', $after)
            ->orderBy('id')->limit(100)->get()
            ->map(fn ($message) => ['id' => $message->id, 'name' => $message->participant->display_name, 'body' => $message->body, 'created_at' => $message->created_at->toIso8601String()]);

        return response()->json(['messages' => $messages]);
    }

    public function store(Request $request, string $code): JsonResponse
    {
        [$meeting, $participant] = $this->participant($request, $code);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $message = MeetingMessage::create(['meeting_id' => $meeting->id, 'participant_id' => $participant->id, 'body' => trim($data['body'])]);

        return response()->json(['message' => ['id' => $message->id, 'name' => $participant->display_name, 'body' => $message->body, 'created_at' => $message->created_at->toIso8601String()]], 201);
    }

    private function participant(Request $request, string $code): array
    {
        $meeting = Meeting::where('code', strtoupper($code))->firstOrFail();
        $id = $request->session()->get('meeting_participant_'.$meeting->id);
        $participant = $id ? MeetingParticipant::where('meeting_id', $meeting->id)->find($id) : null;
        abort_unless($participant && $participant->state === 'admitted' && !$participant->left_at, 403);

        return [$meeting, $participant];
    }
}