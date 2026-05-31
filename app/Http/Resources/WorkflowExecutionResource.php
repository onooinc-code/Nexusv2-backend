<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowExecutionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workflow_id' => $this->workflow_id,
            'workflow_version_id' => $this->workflow_version_id,
            'user_id' => $this->user_id,
            'trigger_source' => $this->trigger_source,
            'run_mode' => $this->run_mode,
            'status' => $this->status,
            'input_payload' => $this->input_payload,
            'runtime_state' => $this->runtime_state,
            'output' => $this->output,
            'error' => $this->error,
            'started_at' => $this->started_at,
            'paused_at' => $this->paused_at,
            'completed_at' => $this->completed_at,
            'cancelled_at' => $this->cancelled_at,
            'step_logs' => WorkflowStepLogResource::collection($this->whenLoaded('stepLogs')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
