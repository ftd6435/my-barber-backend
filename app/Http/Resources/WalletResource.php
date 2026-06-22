<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'currency_id' => $this->currency_id,
            'available_balance' => (float) $this->available_balance,
            'held_balance' => (float) $this->held_balance,
            'is_locked' => (bool) $this->is_locked,
            'currency' => $this->whenLoaded('currency', function () {
                return [
                    'id' => $this->currency?->id,
                    'name' => $this->currency?->name,
                    'code' => $this->currency?->code,
                    'symbol' => $this->currency?->symbol,
                ];
            }),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user?->id,
                    'uuid' => $this->user?->uuid,
                    'first_name' => $this->user?->first_name,
                    'last_name' => $this->user?->last_name,
                    'role' => $this->user?->role,
                ];
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
