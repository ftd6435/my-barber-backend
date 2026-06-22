<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'uuid' => $this->uuid,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'role' => $this->role,
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar_url,
            'default_currency_id' => $this->default_currency_id,
            'default_currency' => $this->whenLoaded('defaultCurrency', function () {
                return [
                    'id' => $this->defaultCurrency?->id,
                    'name' => $this->defaultCurrency?->name,
                    'code' => $this->defaultCurrency?->code,
                    'symbol' => $this->defaultCurrency?->symbol,
                ];
            }),
            'is_phone_verified' => (bool) $this->is_phone_verified,
            'is_email_verified' => (bool) $this->is_email_verified,
            'email_verified_at' => $this->email_verified_at?->toDateTimeString(),
            'phone_verified_at' => $this->phone_verified_at?->toDateTimeString(),
            'is_approved' => (bool) $this->is_approved,
            'is_active' => (bool) $this->is_active,
            'professionel' => $this->whenLoaded('professionel'),
            'client' => $this->whenLoaded('client'),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
