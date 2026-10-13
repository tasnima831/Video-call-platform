<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedInteger('monthly_price_cents')->default(0);
            $table->unsignedInteger('max_duration_minutes')->default(40);
            $table->unsignedInteger('max_participants')->default(10);
            $table->unsignedInteger('monthly_recording_minutes')->default(0);
            $table->unsignedInteger('monthly_ai_summaries')->default(1);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('active');
            $table->string('provider')->default('simulation');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->string('livekit_room')->unique();
            $table->string('title');
            $table->string('status')->default('scheduled');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_limit_minutes')->default(40);
            $table->unsignedInteger('participant_limit')->default(10);
            $table->string('password_hash')->nullable();
            $table->timestamps();
            $table->index(['host_user_id', 'created_at']);
        });

        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('identity')->unique();
            $table->string('display_name', 80);
            $table->string('role')->default('guest');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();
            $table->index(['meeting_id', 'left_at']);
        });

        Schema::create('meeting_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->longText('content');
            $table->boolean('is_shared')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'meeting_id']);
        });

        Schema::create('meeting_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->longText('transcript');
            $table->longText('summary')->nullable();
            $table->json('decisions')->nullable();
            $table->json('action_items')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->index(['meeting_id', 'status']);
        });

        Schema::create('recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('started_by')->constrained('users')->cascadeOnDelete();
            $table->string('egress_id')->nullable()->unique();
            $table->string('storage_disk')->nullable();
            $table->string('storage_path')->nullable();
            $table->string('status')->default('starting');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamps();
        });

        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meeting_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->date('period_start');
            $table->timestamps();
            $table->index(['user_id', 'type', 'period_start']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider');
            $table->string('provider_reference')->nullable()->unique();
            $table->unsignedInteger('amount_cents');
            $table->char('currency', 3)->default('USD');
            $table->string('status')->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        DB::table('plans')->insertOrIgnore([
            ['slug' => 'free', 'name' => 'Free', 'monthly_price_cents' => 0, 'max_duration_minutes' => 40, 'max_participants' => 10, 'monthly_recording_minutes' => 0, 'monthly_ai_summaries' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'premium', 'name' => 'Premium', 'monthly_price_cents' => 0, 'max_duration_minutes' => 240, 'max_participants' => 100, 'monthly_recording_minutes' => 60, 'monthly_ai_summaries' => 30, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('usage_records');
        Schema::dropIfExists('recordings');
        Schema::dropIfExists('meeting_summaries');
        Schema::dropIfExists('meeting_notes');
        Schema::dropIfExists('meeting_participants');
        Schema::dropIfExists('meetings');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
