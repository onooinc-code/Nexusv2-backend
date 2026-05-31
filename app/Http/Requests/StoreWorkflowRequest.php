<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:255', 'unique:workflows,key'],
            'description' => ['nullable', 'string'],
            'is_system' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', 'in:draft,active,archived,paused,completed,failed,cancelled'],
            'steps' => ['required_without:definition', 'array', 'min:1'],
            'definition' => ['required_without:steps', 'array'],
            'definition.steps' => ['required_with:definition', 'array', 'min:1'],
            'trigger_type' => ['required', 'string', 'in:manual,scheduled,event,webhook'],
            'trigger_config' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'change_summary' => ['nullable', 'string'],
        ];
    }
}
