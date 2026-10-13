<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingMessage extends Model
{
    protected $fillable = ['meeting_id', 'participant_id', 'body'];

    public function participant(): BelongsTo { return $this->belongsTo(MeetingParticipant::class); }
}