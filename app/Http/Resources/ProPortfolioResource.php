<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProPortfolioResource extends JsonResource
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
            'professionel_id' => $this->professionel_id,
            'service_id' => $this->service_id,
            'image' => $this->image,
            'image_url' => $this->image_url,
            'is_active' => (bool) $this->is_active,
            'professionel' => $this->whenLoaded('professionel', function () {
                return [
                    'id' => $this->professionel?->id,
                    'uuid' => $this->professionel?->uuid,
                    'first_name' => $this->professionel?->first_name,
                    'last_name' => $this->professionel?->last_name,
                    'username' => $this->professionel?->username,
                ];
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
