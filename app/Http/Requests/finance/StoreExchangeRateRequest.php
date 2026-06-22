<?php

namespace App\Http\Requests\finance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreExchangeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_currency_id' => ['required', 'integer', 'exists:currencies,id', 'different:quote_currency_id'],
            'quote_currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'rate' => ['required', 'numeric', 'gt:0'],
            'source' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'fetched_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'base_currency_id.required' => 'La devise de base est obligatoire.',
            'base_currency_id.exists' => 'La devise de base sélectionnée est invalide.',
            'base_currency_id.different' => 'La devise de base doit être différente de la devise de cotation.',
            'quote_currency_id.required' => 'La devise de cotation est obligatoire.',
            'quote_currency_id.exists' => 'La devise de cotation sélectionnée est invalide.',
            'rate.required' => 'Le taux de change est obligatoire.',
            'rate.numeric' => 'Le taux de change doit être numérique.',
            'rate.gt' => 'Le taux de change doit être supérieur à zéro.',
        ];
    }
}
