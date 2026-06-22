<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalonResource extends JsonResource
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
            'owner_id' => $this->owner_id,
            'name' => $this->name,
            'description' => $this->description,
            'address' => $this->address,
            'salon_phone' => $this->salon_phone,
            'salon_email' => $this->salon_email,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'logo' => $this->logo,
            'logo_url' => $this->logo_url,
            'banner' => $this->banner,
            'banner_url' => $this->banner_url,
            'is_active' => (bool) $this->is_active,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
