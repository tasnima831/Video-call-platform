<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Recording;
use App\Models\UsageRecord;
use App\Services\PlanManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RecordingsController extends Controller
{
    public function store(Request $request, string $code, PlanManager $plans): JsonResponse
    {
        $meeting = Meeting::where('code', strtoupper($code))->firstOrFail();
        abort_unless($request->user()?->id === $meeting->host_user_id, 403);

        $data = $request->validate([
            'recording' => ['required', 'file', 'mimetypes:video/webm,video/mp4,video/quicktime', 'max:204800'],
            'duration_seconds' => ['required', 'integer', 'min:1', 'max:28800'],
        ]);

        $plan = $plans->current($request->user());
        $period = now()->startOfMonth()->toDateString();
        $used = (int) UsageRecord::where('user_id', $request->user()->id)
            ->where('type', 'recording_minutes')->where('period_start', $period)->sum('quantity');
        $minutes = (int) ceil($data['duration_seconds'] / 60);

        if ($minutes + $used > $plan->monthly_recording_minutes) {
            return response()->json(['message' => 'Your plan does not have enough recording minutes.'], 422);
        }

        $path = $data['recording']->store('recordings/'.$meeting->code, 'local');
        $recording = Recording::create([
            'meeting_id' => $meeting->id,
            'started_by' => $request->user()->id,
            'storage_disk' => 'local',
            'storage_path' => $path,
            'status' => 'saved',
            'started_at' => now()->subSeconds($data['duration_seconds']),
            'ended_at' => now(),
            'duration_seconds' => $data['duration_seconds'],
        ]);

        UsageRecord::create([
            'user_id' => $request->user()->id,
            'meeting_id' => $meeting->id,
            'type' => 'recording_minutes',
            'quantity' => $minutes,
            'period_start' => $period,
        ]);

        return response()->json(['recording_id' => $recording->id, 'message' => 'Recording saved privately.'], 201);
    }
}
