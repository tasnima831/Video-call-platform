<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\PlanManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlansController extends Controller
{
    public function index(Request $request, PlanManager $plans): Response
    {
        $current = $plans->current($request->user());

        return Inertia::render('Plans', [
            'plans' => Plan::orderBy('monthly_price_cents')->get(),
            'currentPlan' => $current->slug,
        ]);
    }

    public function choose(Request $request, PlanManager $plans): RedirectResponse
    {
        $data = $request->validate(['plan' => ['required', 'in:free,premium']]);
        $selected = Plan::where('slug', $data['plan'])->firstOrFail();
        $plans->activate($request->user(), $selected);

        return back()->with('status', $selected->name.' plan selected for development preview.');
    }
}
