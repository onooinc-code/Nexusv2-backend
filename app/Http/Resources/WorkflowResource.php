<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'key' => $this->key,
            'description' => $this->description,
            'is_system' => (bool) $this->is_system,
            'owner_id' => $this->owner_id,
            'status' => $this->status,
            'trigger_type' => $this->trigger_type,
            'trigger_config' => $this->trigger_config,
            'version' => $this->version,
            'steps' => $this->steps,
            'settings' => $this->settings,
            'metadata' => $this->metadata,
            'is_active' => (bool) $this->is_active,
            'progress' => $this->progress,
            'total_steps' => $this->total_steps,
            'completed_steps' => $this->completed_steps,
            'execution_count' => $this->execution_count,
            'success_rate' => $this->getSuccessRate(),
            'last_executed_at' => $this->last_executed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
