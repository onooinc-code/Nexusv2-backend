<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResumeWorkflowExecutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['nullable', 'string', 'in:approve,deny'],
            'input_payload' => ['nullable', 'array'],
        ];
    }
}
