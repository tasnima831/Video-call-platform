<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recording extends Model
{
    protected $fillable = ['meeting_id', 'started_by', 'egress_id', 'storage_disk', 'storage_path', 'status', 'started_at', 'ended_at', 'duration_seconds'];
    protected function casts(): array { return ['started_at' => 'datetime', 'ended_at' => 'datetime']; }
}
