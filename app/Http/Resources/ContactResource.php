<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'alternate_name' => $this->alternate_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'whatsapp_number' => $this->whatsapp_number,
            'primary_identifier' => $this->primary_identifier,
            'type' => $this->type,
            'contact_type' => $this->type,
            'gender' => $this->gender,
            'title' => $this->title,
            'company' => $this->company,
            'avatar_url' => $this->avatar_url,
            'metadata' => $this->metadata,
            'attributes' => $this->attributes,
            'is_active' => $this->is_active,
            'reply_mode_override' => $this->reply_mode_override,
            'profile_confidence' => $this->profile_confidence,
            'memory_freshness' => $this->memory_freshness,
            'last_seen_at' => $this->last_seen_at,
            'last_interaction_at' => $this->last_interaction_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
