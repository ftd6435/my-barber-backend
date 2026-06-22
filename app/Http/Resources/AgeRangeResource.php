<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgeRangeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rangeLabel = match (true) {
            $this->min_age !== null && $this->max_age !== null => "{$this->min_age}-{$this->max_age} ans",
            $this->min_age !== null => "{$this->min_age}+ ans",
            $this->max_age !== null => "0-{$this->max_age} ans",
            default => null,
        };

        return [
            'id' => $this->id,
            'name' => $this->name,
            'min_age' => $this->min_age,
            'max_age' => $this->max_age,
            'range' => $rangeLabel,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
