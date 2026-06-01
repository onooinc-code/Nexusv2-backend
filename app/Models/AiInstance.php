<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'provider',
        'model_name',
        'is_active',
        'status',
        'config',
        'routing_tag',
        'workspace_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
}
