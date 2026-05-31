<?php

namespace App\Services\Workflows;

use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkflowRegistry
{
    protected array $validStepTypes = [
        'action',
        'task',
        'decision',
        'parallel',
        'wait',
        'loop',
        'code',
        'compensate',
        'trigger',
        'process',
        'delay',
        'log',
        'condition',
        'agent',
    ];

    public function create(array $data, ?User $user = null): Workflow
    {
        return DB::transaction(function () use ($data, $user) {
            $definition = $this->normalizeDefinition($data);
            $this->validateDefinition($definition);

            $workflow = Workflow::create(array_merge($data, [
                'owner_id' => $data['owner_id'] ?? $user?->id,
                'steps' => $definition['steps'],
                'version' => 1,
            ]));

            $this->createVersion($workflow, $definition, $user, 'Initial workflow definition');

            return $workflow->fresh(['versions']);
        });
    }

    public function update(Workflow $workflow, array $data, ?User $user = null): Workflow
    {
        return DB::transaction(function () use ($workflow, $data, $user) {
            $shouldVersion = array_key_exists('steps', $data) || array_key_exists('definition', $data);

            if ($shouldVersion) {
                $definition = $this->normalizeDefinition(array_merge($workflow->toArray(), $data));
                $this->validateDefinition($definition);
                $data['steps'] = $definition['steps'];
                $data['version'] = ((int) $workflow->version) + 1;
            }

            $workflow->update($data);

            if ($shouldVersion) {
                $this->createVersion($workflow->fresh(), $definition, $user, $data['change_summary'] ?? 'Workflow definition updated');
            }

            return $workflow->fresh(['versions']);
        });
    }

    public function getExecutableVersion(Workflow $workflow): WorkflowVersion
    {
        $version = $workflow->latestVersion();

        if (! $version) {
            $definition = $this->normalizeDefinition($workflow->toArray());
            $this->validateDefinition($definition);
            $version = $this->createVersion($workflow, $definition, null, 'Backfilled executable version');
        }

        return $version;
    }

    public function normalizeDefinition(array $data): array
    {
        $steps = $data['definition']['steps'] ?? $data['steps'] ?? [];

        return [
            'schema_version' => (int) ($data['definition']['schema_version'] ?? 1),
            'steps' => array_values(array_map(fn (array $step, int $index) => $this->normalizeStep($step, $index), $steps, array_keys($steps))),
            'edges' => $data['definition']['edges'] ?? [],
            'settings' => $data['settings'] ?? [],
        ];
    }

    public function validateDefinition(array $definition): void
    {
        if (empty($definition['steps']) || ! is_array($definition['steps'])) {
            throw ValidationException::withMessages(['steps' => 'Workflow must have at least one step.']);
        }

        $ids = [];
        foreach ($definition['steps'] as $index => $step) {
            $type = strtolower((string) ($step['type'] ?? 'action'));
            if (! in_array($type, $this->validStepTypes, true)) {
                throw ValidationException::withMessages(["steps.{$index}.type" => "Unsupported workflow step type: {$type}."]);
            }

            if (empty($step['name'])) {
                throw ValidationException::withMessages(["steps.{$index}.name" => 'Step name is required.']);
            }

            if (isset($ids[$step['id']])) {
                throw ValidationException::withMessages(["steps.{$index}.id" => "Duplicate step id: {$step['id']}."]);
            }
            $ids[$step['id']] = true;
        }
    }

    protected function normalizeStep(array $step, int $index): array
    {
        $legacyType = $step['action'] ?? $step['type'] ?? 'action';
        $type = match ($legacyType) {
            'condition' => 'decision',
            'delay' => 'wait',
            'process' => 'action',
            default => strtolower((string) $legacyType),
        };

        return array_merge($step, [
            'id' => (string) ($step['id'] ?? $step['key'] ?? 'step_' . ($index + 1)),
            'name' => (string) ($step['name'] ?? $step['title'] ?? 'Step ' . ($index + 1)),
            'type' => $type,
            'step_order' => (int) ($step['step_order'] ?? $step['order'] ?? $index + 1),
        ]);
    }

    protected function createVersion(Workflow $workflow, array $definition, ?User $user, ?string $summary): WorkflowVersion
    {
        return WorkflowVersion::create([
            'id' => (string) Str::uuid(),
            'workflow_id' => $workflow->id,
            'version_number' => (int) $workflow->version,
            'definition' => $definition,
            'created_by' => $user?->id,
            'change_summary' => $summary,
        ]);
    }
}
