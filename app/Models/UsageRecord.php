<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsageRecord extends Model
{
    protected $fillable = ['user_id', 'meeting_id', 'type', 'quantity', 'period_start'];
    protected function casts(): array { return ['quantity' => 'decimal:2', 'period_start' => 'date']; }
}
