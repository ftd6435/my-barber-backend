<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
            'salon_id' => $this->salon_id,
            'category_id' => $this->category_id,
            'currency_id' => $this->currency_id,
            'name' => $this->name,
            'duration_minutes' => $this->duration_minutes,
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
            'salon' => $this->whenLoaded('salon', function () {
                return [
                    'id' => $this->salon?->id,
                    'uuid' => $this->salon?->uuid,
                    'name' => $this->salon?->name,
                    'address' => $this->salon?->address,
                    'city' => $this->salon?->city,
                    'logo_url' => $this->salon?->logo_url,
                ];
            }),
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category?->id,
                    'name' => $this->category?->name,
                    'description' => $this->category?->description,
                ];
            }),
            'currency' => $this->whenLoaded('currency', function () {
                return [
                    'id' => $this->currency?->id,
                    'name' => $this->currency?->name,
                    'code' => $this->currency?->code,
                    'symbol' => $this->currency?->symbol,
                ];
            }),
            'service_prices' => ServicePriceResource::collection($this->whenLoaded('servicePrices')),
            'portfolios' => ProPortfolioResource::collection($this->whenLoaded('portfolios')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
        ];
    }
}
