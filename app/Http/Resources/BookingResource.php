<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $bookingPrices = $this->relationLoaded('bookingPrices') ? $this->bookingPrices : collect();
        $subtotal = $bookingPrices->sum(function ($bookingPrice) {
            return (float) $bookingPrice->price * (int) $bookingPrice->number;
        });
        $extraFees = (float) $this->extra_fees;

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'professionel_id' => $this->professionel_id,
            'client_id' => $this->client_id,
            'service_id' => $this->service_id,
            'booking_date' => $this->booking_date?->toDateString(),
            'start_time' => $this->start_time?->format('H:i:s'),
            'end_time' => $this->end_time?->format('H:i:s'),
            'location' => $this->location,
            'client_address' => $this->client_address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'booking_details' => $this->booking_details,
            'cancel_reason' => $this->cancel_reason,
            'professionel_comment' => $this->professionel_comment,
            'extra_fees' => $extraFees,
            'subtotal' => round((float) $subtotal, 2),
            'total' => round((float) $subtotal + $extraFees, 2),
            'professionel' => $this->whenLoaded('professionel', function () {
                return [
                    'id' => $this->professionel?->id,
                    'uuid' => $this->professionel?->uuid,
                    'first_name' => $this->professionel?->first_name,
                    'last_name' => $this->professionel?->last_name,
                    'telephone' => $this->professionel?->telephone,
                    'email' => $this->professionel?->email,
                ];
            }),
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client?->id,
                    'uuid' => $this->client?->uuid,
                    'first_name' => $this->client?->first_name,
                    'last_name' => $this->client?->last_name,
                    'telephone' => $this->client?->telephone,
                    'email' => $this->client?->email,
                ];
            }),
            'service' => $this->whenLoaded('service', function () {
                return [
                    'id' => $this->service?->id,
                    'name' => $this->service?->name,
                    'duration_minutes' => $this->service?->duration_minutes,
                    'salon_id' => $this->service?->salon_id,
                    'category_id' => $this->service?->category_id,
                ];
            }),
            'booking_prices' => BookingPriceResource::collection($bookingPrices),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
        ];
    }
}
