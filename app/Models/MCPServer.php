<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MCPServer extends BaseModel
{
    use HasFactory;
    
    public $incrementing = false;

    protected $keyType = 'string';
    
    protected $table = 'mcp_servers';

    protected $fillable = [
        'id',
        'name',
        'description',
        'type',
        'connection_config',
        'status',
        'is_default',
    ];

    protected $casts = [
        'connection_config' => 'json',
    ];

    public function agents()
    {
        return $this->belongsToMany(Agent::class, 'agent_mcp_servers');
    }
}
