<?php

namespace App\Http\Requests\activities;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreServicePriceRequest extends FormRequest
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
            'age_range_id' => ['required', 'integer', 'exists:age_ranges,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_approved' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_id.required' => 'Le service est obligatoire.',
            'service_id.exists' => 'Le service sélectionné est invalide.',
            'age_range_id.required' => 'La tranche d\'âge est obligatoire.',
            'age_range_id.exists' => 'La tranche d\'âge sélectionnée est invalide.',
            'price.required' => 'Le prix est obligatoire.',
            'price.numeric' => 'Le prix doit être un nombre valide.',
        ];
    }
}
