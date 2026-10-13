<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

class PlanManager
{
    public function current(User $user): Plan
    {
        $subscription = Subscription::with('plan')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->latest('id')
            ->first();

        if ($subscription) {
            return $subscription->plan;
        }

        $free = Plan::firstOrCreate(
            ['slug' => 'free'],
            ['name' => 'Free', 'monthly_price_cents' => 0, 'max_duration_minutes' => 40, 'max_participants' => 10, 'monthly_recording_minutes' => 0, 'monthly_ai_summaries' => 1]
        );

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $free->id,
            'status' => 'active',
            'provider' => 'simulation',
            'starts_at' => now(),
        ]);

        return $free;
    }

    public function activate(User $user, Plan $plan): Subscription
    {
        Subscription::where('user_id', $user->id)->where('status', 'active')
            ->update(['status' => 'replaced', 'ends_at' => now()]);

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'provider' => 'simulation',
            'starts_at' => now(),
        ]);
    }
}
