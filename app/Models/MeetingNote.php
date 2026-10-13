<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingNote extends Model
{
    protected $fillable = ['meeting_id', 'user_id', 'content', 'is_shared'];

    protected function casts(): array
    {
        return ['is_shared' => 'boolean'];
    }

    public function meeting(): BelongsTo { return $this->belongsTo(Meeting::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
