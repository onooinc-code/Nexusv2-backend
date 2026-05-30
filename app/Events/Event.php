<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base Event class for all Nexus events
 */
abstract class Event
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Event metadata
     *
     * @var array
     */
    public array $metadata = [];

    /**
     * Event timestamp
     *
     * @var \Carbon\Carbon
     */
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct()
    {
        $this->timestamp = now();
    }

    /**
     * Set event metadata
     *
     * @param array $metadata
     * @return $this
     */
    public function withMetadata(array $metadata): static
    {
        $this->metadata = array_merge($this->metadata, $metadata);
        return $this;
    }

    /**
     * Get event metadata
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getMetadata(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->metadata;
        }

        return $this->metadata[$key] ?? $default;
    }
}
