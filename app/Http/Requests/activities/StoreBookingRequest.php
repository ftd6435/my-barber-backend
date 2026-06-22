<?php

namespace App\Http\Requests\activities;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class StoreBookingRequest extends FormRequest
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
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'client_currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'location' => ['nullable', Rule::in(['home', 'salon'])],
            'start_time' => ['required', 'date_format:H:i'],
            'booking_details' => ['nullable', 'array'],
            'client_address' => ['nullable', 'string', 'min:2', 'required_if:location,home'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'age_ranges' => ['required', 'array', 'min:1'],
            'age_ranges.*.age_range_id' => ['required', 'integer', 'exists:age_ranges,id', 'distinct'],
            'age_ranges.*.number' => ['required', 'integer', 'min:1'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'service_id.required' => 'Le service est obligatoire.',
            'service_id.exists' => 'Le service sélectionné est invalide.',
            'client_currency_id.exists' => 'La devise de paiement sélectionnée est invalide.',
            'booking_date.required' => 'La date de réservation est obligatoire.',
            'booking_date.after_or_equal' => 'La date de réservation doit être aujourd\'hui ou ultérieure.',
            'location.in' => 'Le lieu de réservation sélectionné est invalide.',
            'start_time.required' => 'L\'heure de début est obligatoire.',
            'start_time.date_format' => 'L\'heure de début doit être au format HH:MM.',
            'client_address.required_if' => 'L\'adresse du client est obligatoire pour une prestation à domicile.',
            'latitude.between' => 'La latitude doit être comprise entre -90 et 90.',
            'longitude.between' => 'La longitude doit être comprise entre -180 et 180.',
            'age_ranges.required' => 'Au moins une tranche d\'âge est obligatoire.',
            'age_ranges.min' => 'Au moins une tranche d\'âge est obligatoire.',
            'age_ranges.*.age_range_id.required' => 'La tranche d\'âge est obligatoire.',
            'age_ranges.*.age_range_id.exists' => 'La tranche d\'âge sélectionnée est invalide.',
            'age_ranges.*.age_range_id.distinct' => 'Chaque tranche d\'âge ne peut être utilisée qu\'une seule fois.',
            'age_ranges.*.number.required' => 'Le nombre de personnes est obligatoire.',
            'age_ranges.*.number.min' => 'Le nombre de personnes doit être supérieur à zéro.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $location = $this->input('location');

        $this->merge([
            'location' => $location ? strtolower(trim((string) $location)) : 'home',
            'client_currency_id' => $this->input('client_currency_id') ?: $this->user()?->default_currency_id,
        ]);
    }
}
