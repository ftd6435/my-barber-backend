<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingPriceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lineTotal = (float) $this->price * (int) $this->number;

        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'age_range_id' => $this->age_range_id,
            'currency_id' => $this->currency_id,
            'number' => (int) $this->number,
            'price' => (float) $this->price,
            'line_total' => round($lineTotal, 2),
            'age_range' => $this->whenLoaded('ageRange', function () {
                return [
                    'id' => $this->ageRange?->id,
                    'name' => $this->ageRange?->name,
                    'min_age' => $this->ageRange?->min_age,
                    'max_age' => $this->ageRange?->max_age,
                ];
            }),
            'currency' => $this->whenLoaded('currency', function () {
                return [
                    'id' => $this->currency?->id,
                    'code' => $this->currency?->code,
                    'symbol' => $this->currency?->symbol,
                ];
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
