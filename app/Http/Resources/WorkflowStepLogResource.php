<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowStepLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'execution_id' => $this->execution_id,
            'workflow_id' => $this->workflow_id,
            'step_id' => $this->step_id,
            'step_name' => $this->step_name,
            'step_type' => $this->step_type,
            'status' => $this->status,
            'input' => $this->input,
            'output' => $this->output,
            'error' => $this->error,
            'attempt' => $this->attempt,
            'duration_ms' => $this->duration_ms,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
        ];
    }
}
