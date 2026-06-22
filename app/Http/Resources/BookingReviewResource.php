<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingReviewResource extends JsonResource
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
            'booking_id' => $this->booking_id,
            'client_id' => $this->client_id,
            'professionel_id' => $this->professionel_id,
            'review' => $this->review,
            'rating' => $this->rating,
            'is_visible' => (bool) $this->is_visible,
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client?->id,
                    'uuid' => $this->client?->uuid,
                    'first_name' => $this->client?->first_name,
                    'last_name' => $this->client?->last_name,
                    'avatar_url' => $this->client?->avatar_url,
                ];
            }),
            'professionel' => $this->whenLoaded('professionel', function () {
                return [
                    'id' => $this->professionel?->id,
                    'uuid' => $this->professionel?->uuid,
                    'first_name' => $this->professionel?->first_name,
                    'last_name' => $this->professionel?->last_name,
                    'avatar_url' => $this->professionel?->avatar_url,
                ];
            }),
            'booking' => $this->whenLoaded('booking', function () {
                return [
                    'id' => $this->booking?->id,
                    'reference' => $this->booking?->reference,
                    'status' => $this->booking?->status,
                    'booking_date' => $this->booking?->booking_date?->toDateString(),
                    'service_id' => $this->booking?->service_id,
                    'service_name' => $this->booking?->service?->name,
                ];
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
