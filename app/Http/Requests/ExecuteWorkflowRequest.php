<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExecuteWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'run_mode' => ['sometimes', 'string', 'in:sync,async'],
            'input_payload' => ['nullable', 'array'],
            'context' => ['nullable', 'array'],
            'trigger_source' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function inputPayload(): array
    {
        return $this->validated('input_payload') ?? $this->validated('context') ?? [];
    }
}
