<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:draft,active,archived,paused,completed,failed,cancelled'],
            'steps' => ['sometimes', 'array', 'min:1'],
            'definition' => ['sometimes', 'array'],
            'definition.steps' => ['required_with:definition', 'array', 'min:1'],
            'trigger_type' => ['sometimes', 'string', 'in:manual,scheduled,event,webhook'],
            'trigger_config' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
            'change_summary' => ['nullable', 'string'],
        ];
    }
}
