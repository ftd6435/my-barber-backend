<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicePriceResource extends JsonResource
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
            'service_id' => $this->service_id,
            'age_range_id' => $this->age_range_id,
            'price' => $this->price,
            'is_approved' => (bool) $this->is_approved,
            'age_range' => $this->whenLoaded('ageRange', function () {
                return [
                    'id' => $this->ageRange?->id,
                    'name' => $this->ageRange?->name,
                    'min_age' => $this->ageRange?->min_age,
                    'max_age' => $this->ageRange?->max_age,
                ];
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
