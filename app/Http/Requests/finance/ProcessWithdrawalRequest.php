<?php

namespace App\Http\Requests\finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['approved', 'rejected', 'paid'])],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Le statut de traitement est obligatoire.',
            'status.in' => 'Le statut de traitement sélectionné est invalide.',
        ];
    }
}
