<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMemoryMaintenanceRun extends Model
{
    protected $fillable = [
        'status',
        'operation',
        'scope',
        'results',
        'error_log',
    ];

    protected $casts = [
        'scope' => 'array',
        'results' => 'array',
    ];
}
