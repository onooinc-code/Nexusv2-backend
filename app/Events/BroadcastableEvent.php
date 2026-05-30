<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;

abstract class BroadcastableEvent extends Event implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    /**
     * Get the data to broadcast for the event.
     * Ensures full Eloquent models are sanitized into safe identifiers.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->sanitizePayload(get_object_vars($this));
    }

    /**
     * Recursively sanitize data payloads.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function sanitizeValue(mixed $value): mixed
    {
        if ($value instanceof Model) {
            return [
                'id' => $value->getKey(),
                'type' => class_basename($value),
            ];
        }

        if ($value instanceof SupportCollection) {
            return $value->map(fn ($item) => $this->sanitizeValue($item))->all();
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->sanitizeValue($item), $value);
        }

        return $value;
    }

    protected function sanitizePayload(array $payload): array
    {
        return array_map(fn ($value) => $this->sanitizeValue($value), $payload);
    }
}
