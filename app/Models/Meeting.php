<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meeting extends Model
{
    protected $fillable = [
        'host_user_id', 'code', 'livekit_room', 'title', 'status',
        'started_at', 'ended_at', 'duration_limit_minutes',
        'participant_limit', 'password_hash',
    ];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function host(): BelongsTo { return $this->belongsTo(User::class, 'host_user_id'); }
    public function participants(): HasMany { return $this->hasMany(MeetingParticipant::class); }
    public function notes(): HasMany { return $this->hasMany(MeetingNote::class); }
}
