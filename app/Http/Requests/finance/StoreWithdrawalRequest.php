<?php

namespace App\Http\Requests\finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'destination_details' => ['nullable', 'array'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'wallet_id.required' => 'Le wallet est obligatoire.',
            'wallet_id.exists' => 'Le wallet sélectionné est invalide.',
            'amount.required' => 'Le montant du retrait est obligatoire.',
            'amount.numeric' => 'Le montant du retrait doit être numérique.',
            'amount.gt' => 'Le montant du retrait doit être supérieur à zéro.',
        ];
    }
}
