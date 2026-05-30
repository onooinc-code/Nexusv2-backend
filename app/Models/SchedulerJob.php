<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchedulerJob extends Model
{
    protected $fillable = [
        'name',
        'type',
        'payload',
        'cron_expression',
        'status',
        'is_running',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'is_running' => 'boolean',
        'payload' => 'array',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];
}
