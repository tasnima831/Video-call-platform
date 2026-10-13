<?php

namespace App\Http\Controllers;

use App\Models\MeetingSummary;
use App\Models\UsageRecord;
use App\Services\PlanManager;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SummariesController extends Controller
{
    public function index(Request $request): Response
    {
        $summaries = MeetingSummary::where('user_id', $request->user()->id)
            ->latest()->limit(30)->get(['id', 'meeting_id', 'transcript', 'summary', 'decisions', 'action_items', 'status', 'created_at']);

        return Inertia::render('Summaries', ['savedSummaries' => $summaries]);
    }

    public function store(Request $request, PlanManager $plans): JsonResponse
    {
        $data = $request->validate([
            'transcript' => ['required', 'string', 'min:20', 'max:100000'],
            'meeting_id' => ['nullable', 'integer', 'exists:meetings,id'],
        ]);

        $plan = $plans->current($request->user());
        $used = MeetingSummary::where('user_id', $request->user()->id)
            ->where('created_at', '>=', now()->startOfMonth())->count();

        if ($used >= $plan->monthly_ai_summaries) {
            return response()->json(['message' => 'Your monthly AI summary allowance has been reached.'], 422);
        }

        if (!empty($data['meeting_id'])) {
            $authorized = $request->user()->id === \App\Models\Meeting::findOrFail($data['meeting_id'])->host_user_id
                || \App\Models\MeetingParticipant::where('meeting_id', $data['meeting_id'])->where('user_id', $request->user()->id)->exists();
            abort_unless($authorized, 403);
        }

        $summary = MeetingSummary::create([
            'meeting_id' => $data['meeting_id'] ?? null,
            'user_id' => $request->user()->id,
            'transcript' => $data['transcript'],
            'status' => 'pending',
        ]);

        $endpoint = config('services.ai_summary.url');
        $key = config('services.ai_summary.key');
        if (!$endpoint || !$key) {
            return response()->json([
                'id' => $summary->id,
                'status' => 'pending',
                'message' => 'Transcript saved. Configure AI_SUMMARY_URL and AI_SUMMARY_KEY to generate a summary.',
            ], 202);
        }

        $response = Http::timeout(60)->withToken($key)->post($endpoint, [
            'transcript' => $summary->transcript,
        ]);

        if (!$response->successful()) {
            $summary->update(['status' => 'failed']);
            return response()->json(['message' => 'The AI provider could not process this transcript.'], 502);
        }

        $result = $response->json();
        $summary->update([
            'summary' => $result['summary'] ?? null,
            'decisions' => $result['decisions'] ?? [],
            'action_items' => $result['action_items'] ?? [],
            'status' => 'completed',
        ]);
        UsageRecord::create([
            'user_id' => $request->user()->id,
            'type' => 'ai_summary',
            'quantity' => 1,
            'period_start' => now()->startOfMonth()->toDateString(),
        ]);

        return response()->json(['summary' => $summary->fresh()]);
    }
}
