<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wallet_id' => $this->wallet_id,
            'user_id' => $this->user_id,
            'currency_id' => $this->currency_id,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'destination_details' => $this->destination_details,
            'comment' => $this->comment,
            'processed_by' => $this->processed_by,
            'processed_at' => $this->processed_at?->toDateTimeString(),
            'wallet' => $this->whenLoaded('wallet', fn () => new WalletResource($this->wallet)),
            'currency' => $this->whenLoaded('currency', function () {
                return [
                    'id' => $this->currency?->id,
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
            'processor' => $this->whenLoaded('processedBy', function () {
                return [
                    'id' => $this->processedBy?->id,
                    'uuid' => $this->processedBy?->uuid,
                    'first_name' => $this->processedBy?->first_name,
                    'last_name' => $this->processedBy?->last_name,
                    'role' => $this->processedBy?->role,
                ];
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
