<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AgentPersona extends BaseModel
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'description',
        'system_prompt',
        'tone_preferences',
    ];

    protected $casts = [
        'tone_preferences' => 'json',
    ];

    public function agents()
    {
        return $this->hasMany(Agent::class, 'persona_id');
    }
}
