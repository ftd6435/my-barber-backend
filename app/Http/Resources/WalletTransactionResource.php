<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wallet_id' => $this->wallet_id,
            'user_id' => $this->user_id,
            'currency_id' => $this->currency_id,
            'type' => $this->type,
            'direction' => $this->direction,
            'balance_type' => $this->balance_type,
            'status' => $this->status,
            'amount' => (float) $this->amount,
            'balance_before' => (float) $this->balance_before,
            'balance_after' => (float) $this->balance_after,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'description' => $this->description,
            'meta' => $this->meta,
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
