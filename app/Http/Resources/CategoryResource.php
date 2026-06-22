<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_by_user' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy?->id,
                    'uuid' => $this->createdBy?->uuid,
                    'first_name' => $this->createdBy?->first_name,
                    'last_name' => $this->createdBy?->last_name,
                    'username' => $this->createdBy?->username,
                    'role' => $this->createdBy?->role,
                ];
            }),
            'updated_by_user' => $this->whenLoaded('updatedBy', function () {
                return [
                    'id' => $this->updatedBy?->id,
                    'uuid' => $this->updatedBy?->uuid,
                    'first_name' => $this->updatedBy?->first_name,
                    'last_name' => $this->updatedBy?->last_name,
                    'username' => $this->updatedBy?->username,
                    'role' => $this->updatedBy?->role,
                ];
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
