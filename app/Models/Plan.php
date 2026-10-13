<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plan extends Model
{
    protected $fillable = ['slug', 'name', 'monthly_price_cents', 'max_duration_minutes', 'max_participants', 'monthly_recording_minutes', 'monthly_ai_summaries'];
    protected function casts(): array { return ['monthly_price_cents' => 'integer']; }
}
