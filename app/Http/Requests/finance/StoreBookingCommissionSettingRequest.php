<?php

namespace App\Http\Requests\finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingCommissionSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'percentage.required' => 'Le pourcentage de commission est obligatoire.',
            'percentage.numeric' => 'Le pourcentage de commission doit être numérique.',
            'percentage.min' => 'Le pourcentage de commission ne peut pas être négatif.',
            'percentage.max' => 'Le pourcentage de commission ne peut pas dépasser 100.',
        ];
    }
}
