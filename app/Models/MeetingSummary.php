<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingSummary extends Model
{
    protected $fillable = ['meeting_id', 'user_id', 'transcript', 'summary', 'decisions', 'action_items', 'status'];
    protected function casts(): array { return ['decisions' => 'array', 'action_items' => 'array']; }
}
