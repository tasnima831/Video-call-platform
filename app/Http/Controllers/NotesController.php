<?php

namespace App\Http\Controllers;

use App\Models\MeetingNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotesController extends Controller
{
    public function index(Request $request): Response
    {
        $note = MeetingNote::where('user_id', $request->user()->id)
            ->whereNull('meeting_id')->first();

        return Inertia::render('Notes', ['initialNote' => $note?->content ?? '']);
    }

    public function save(Request $request): JsonResponse
    {
        $data = $request->validate(['content' => ['present', 'string', 'max:100000']]);
        $note = MeetingNote::firstOrNew([
            'user_id' => $request->user()->id,
            'meeting_id' => null,
        ]);
        $note->content = $data['content'];
        $note->is_shared = false;
        $note->save();

        return response()->json(['saved_at' => $note->updated_at->toIso8601String()]);
    }
}

