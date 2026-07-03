<?php

namespace App\Http\Requests\djomy;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingPaymentLinkRequest extends FormRequest
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
            'countryCode' => ['required', 'string', 'size:2'],
            'amountToPay' => ['required', 'numeric', 'min:1'],
            'linkName' => ['nullable', 'string', 'max:255'],
            'phoneNumber' => ['nullable', 'string'],
            'sendSms' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:255'],
            'usageType' => ['nullable', Rule::in(['UNIQUE', 'MULTIPLE'])],
            'usageLimit' => ['nullable', 'integer', 'min:1'],
            'expiresAt' => ['nullable', 'date_format:Y-m-d\TH:i:s\Z'],
            'returnUrl' => ['nullable', 'url', 'starts_with:https'],
            'cancelUrl' => ['nullable', 'url', 'starts_with:https'],
            'allowedPaymentMethods' => ['nullable', 'array'],
            'allowedPaymentMethods.*' => ['string', Rule::in(['OM', 'MOMO', 'SOUTRA_MONEY', 'PAYCARD', 'CARD'])],
            'customFields' => ['nullable', 'array'],
            'customFields.*.label' => ['required_with:customFields', 'string'],
            'customFields.*.placeholder' => ['nullable', 'string'],
            'customFields.*.required' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_id.required' => 'La réservation est obligatoire.',
            'booking_id.exists' => 'La réservation sélectionnée est invalide.',
            'countryCode.required' => 'Le code pays est obligatoire.',
            'countryCode.size' => 'Le code pays doit contenir exactement 2 caractères.',
            'amountToPay.required' => 'Le montant à payer est obligatoire.',
            'amountToPay.numeric' => 'Le montant à payer doit être un nombre valide.',
            'amountToPay.min' => 'Le montant à payer doit être supérieur à zéro.',
            'linkName.max' => 'Le nom du lien ne doit pas dépasser 255 caractères.',
            'description.max' => 'La description ne doit pas dépasser 255 caractères.',
            'usageType.in' => 'Le type d\'utilisation sélectionné est invalide.',
            'usageLimit.integer' => 'La limite d\'utilisation doit être un nombre entier.',
            'usageLimit.min' => 'La limite d\'utilisation doit être supérieure à zéro.',
            'expiresAt.date_format' => 'La date d\'expiration doit respecter le format ISO attendu.',
            'returnUrl.url' => 'L\'URL de retour doit être une URL valide.',
            'returnUrl.starts_with' => 'L\'URL de retour doit commencer par https.',
            'cancelUrl.url' => 'L\'URL d\'annulation doit être une URL valide.',
            'cancelUrl.starts_with' => 'L\'URL d\'annulation doit commencer par https.',
            'allowedPaymentMethods.array' => 'Les moyens de paiement autorisés doivent être fournis sous forme de tableau.',
            'allowedPaymentMethods.*.in' => 'Un des moyens de paiement autorisés est invalide.',
            'metadata.array' => 'Les métadonnées doivent être sous forme de tableau.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'countryCode' => strtoupper((string) $this->input('countryCode')),
            'usageType' => strtoupper((string) ($this->input('usageType') ?? 'UNIQUE')),
        ]);
    }
}
