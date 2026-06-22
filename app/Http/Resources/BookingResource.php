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
            'service_currency_id' => $this->service_currency_id,
            'client_currency_id' => $this->client_currency_id,
            'settlement_currency_id' => $this->settlement_currency_id,
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
            'service_to_client_exchange_rate' => (float) $this->service_to_client_exchange_rate,
            'service_subtotal_amount' => round((float) $this->service_subtotal_amount ?: (float) $subtotal, 2),
            'service_total_amount' => round((float) $this->service_total_amount ?: ((float) $subtotal + $extraFees), 2),
            'client_total_amount' => round((float) $this->client_total_amount, 2),
            'settlement_total_amount' => round((float) $this->settlement_total_amount, 2),
            'client_refunded_amount' => round((float) $this->client_refunded_amount, 2),
            'platform_fee_percentage' => round((float) $this->platform_fee_percentage, 2),
            'platform_fee_amount' => round((float) $this->platform_fee_amount, 2),
            'professionel_net_amount' => round((float) $this->professionel_net_amount, 2),
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
                    'currency_id' => $this->service?->currency_id,
                ];
            }),
            'service_currency' => $this->whenLoaded('serviceCurrency', function () {
                return [
                    'id' => $this->serviceCurrency?->id,
                    'name' => $this->serviceCurrency?->name,
                    'code' => $this->serviceCurrency?->code,
                    'symbol' => $this->serviceCurrency?->symbol,
                ];
            }),
            'client_currency' => $this->whenLoaded('clientCurrency', function () {
                return [
                    'id' => $this->clientCurrency?->id,
                    'name' => $this->clientCurrency?->name,
                    'code' => $this->clientCurrency?->code,
                    'symbol' => $this->clientCurrency?->symbol,
                ];
            }),
            'settlement_currency' => $this->whenLoaded('settlementCurrency', function () {
                return [
                    'id' => $this->settlementCurrency?->id,
                    'name' => $this->settlementCurrency?->name,
                    'code' => $this->settlementCurrency?->code,
                    'symbol' => $this->settlementCurrency?->symbol,
                ];
            }),
            'booking_prices' => BookingPriceResource::collection($bookingPrices),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
        ];
    }
}
