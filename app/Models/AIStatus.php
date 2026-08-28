<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIStatus extends Model
{
    protected $table = 'ai_statuses';
    
    protected $fillable = [
        'model_name',
        'is_online',
        'response_time_ms',
        'last_check_at',
        'last_error',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'last_check_at' => 'datetime',
    ];
}
