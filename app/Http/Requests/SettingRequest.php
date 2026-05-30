<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * SettingRequest
 *
 * Validates setting creation and update payloads.
 * Enforces unique keys on create, allows partial updates.
 */
class SettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // TODO: Gate check for admin role when auth is implemented
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $settingId = $this->route('setting');

        return [
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('settings', 'key')->ignore($settingId),
            ],
            'value' => ['required'],
            'type' => [
                'required',
                'string',
                'in:string,integer,boolean,json,text',
            ],
            'group' => ['required', 'string', 'max:255'],
            'is_public' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'key.required' => 'A setting key is required.',
            'key.unique' => 'This setting key already exists.',
            'value.required' => 'A setting value is required.',
            'type.required' => 'A setting type is required.',
            'type.in' => 'The type must be one of: string, integer, boolean, json, text.',
            'group.required' => 'A setting group is required.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_public')) {
            $this->merge([
                'is_public' => filter_var($this->input('is_public'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
