<?php

namespace App\Http\Requests\djomy;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiateBookingPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'paymentMethod' => ['required', 'string', Rule::in(['OM', 'MOMO', 'KULU', 'YMO', 'SOUTRA_MONEY', 'PAYCARD'])],
            'payerIdentifier' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
            'countryCode' => ['nullable', 'string', 'size:2'],
            'description' => ['nullable', 'string', 'max:255'],
            'returnUrl' => ['nullable', 'url'],
            'cancelUrl' => ['nullable', 'url'],
            'metadata' => ['nullable', 'array'],
            'metadata.*' => ['scalar'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_id.required' => 'La réservation est obligatoire.',
            'booking_id.exists' => 'La réservation sélectionnée est invalide.',
            'paymentMethod.required' => 'Le moyen de paiement est obligatoire.',
            'paymentMethod.in' => 'Le moyen de paiement sélectionné est invalide.',
            'payerIdentifier.required' => 'L\'identifiant du payeur est obligatoire.',
            'amount.required' => 'Le montant est obligatoire.',
            'amount.numeric' => 'Le montant doit être un nombre valide.',
            'amount.min' => 'Le montant doit être supérieur à zéro.',
            'countryCode.size' => 'Le code pays doit contenir exactement 2 caractères.',
            'description.max' => 'La description ne doit pas dépasser 255 caractères.',
            'returnUrl.url' => 'L\'URL de retour doit être une URL valide.',
            'cancelUrl.url' => 'L\'URL d\'annulation doit être une URL valide.',
            'metadata.array' => 'Les métadonnées doivent être sous forme de tableau.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'paymentMethod' => strtoupper((string) $this->input('paymentMethod')),
            'countryCode' => strtoupper((string) ($this->input('countryCode') ?? 'GN')),
        ]);
    }
}
