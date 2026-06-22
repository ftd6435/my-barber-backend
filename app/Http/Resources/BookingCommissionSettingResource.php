<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingCommissionSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'percentage' => round((float) $this->percentage, 2),
            'is_active' => (bool) $this->is_active,
            'updated_by' => $this->updated_by,
            'updated_by_user' => $this->whenLoaded('updatedBy', function () {
                return [
                    'id' => $this->updatedBy?->id,
                    'uuid' => $this->updatedBy?->uuid,
                    'first_name' => $this->updatedBy?->first_name,
                    'last_name' => $this->updatedBy?->last_name,
                    'role' => $this->updatedBy?->role,
                ];
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
