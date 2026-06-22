<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'base_currency_id' => $this->base_currency_id,
            'quote_currency_id' => $this->quote_currency_id,
            'rate' => (float) $this->rate,
            'source' => $this->source,
            'is_active' => (bool) $this->is_active,
            'fetched_at' => $this->fetched_at?->toDateTimeString(),
            'base_currency' => $this->whenLoaded('baseCurrency', function () {
                return [
                    'id' => $this->baseCurrency?->id,
                    'code' => $this->baseCurrency?->code,
                    'symbol' => $this->baseCurrency?->symbol,
                ];
            }),
            'quote_currency' => $this->whenLoaded('quoteCurrency', function () {
                return [
                    'id' => $this->quoteCurrency?->id,
                    'code' => $this->quoteCurrency?->code,
                    'symbol' => $this->quoteCurrency?->symbol,
                ];
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
