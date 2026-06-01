<?php

namespace App\Services;

use App\Models\AgentPersona;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AgentPersonaService
{
    /**
     * Create a new agent persona.
     *
     * @param array $data
     * @return AgentPersona
     * @throws ValidationException
     */
    public function createPersona(array $data): AgentPersona
    {
        $this->validate($data);

        return AgentPersona::create([
            'workspace_id' => $data['workspace_id'],
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'system_prompt'=> $data['system_prompt'],
            'temperature'  => $data['temperature'] ?? 0.7,
            'max_tokens'   => $data['max_tokens'] ?? null,
            'tools_config' => $data['tools_config'] ?? [],
            'is_public'    => $data['is_public'] ?? false,
        ]);
    }

    /**
     * Update an existing persona.
     *
     * @param AgentPersona $persona
     * @param array $data
     * @return AgentPersona
     * @throws ValidationException
     */
    public function updatePersona(AgentPersona $persona, array $data): AgentPersona
    {
        $this->validate($data, true);

        $persona->update($data);

        return $persona;
    }

    /**
     * Validate persona data.
     *
     * @param array $data
     * @param bool $isUpdate
     * @return void
     * @throws ValidationException
     */
    protected function validate(array $data, bool $isUpdate = false): void
    {
        $rules = [
            'workspace_id'  => $isUpdate ? 'sometimes|exists:workspaces,id' : 'required|exists:workspaces,id',
            'name'          => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            'description'   => 'nullable|string',
            'system_prompt' => $isUpdate ? 'sometimes|string' : 'required|string',
            'temperature'   => 'nullable|numeric|min:0|max:2',
            'max_tokens'    => 'nullable|integer|min:1',
            'tools_config'  => 'nullable|array',
            'is_public'     => 'boolean',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
