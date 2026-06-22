<?php

namespace App\Http\Requests\acteur;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class UpsertProfessionelProfileRequest extends FormRequest
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
            'business_name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'experience_years' => ['nullable', 'integer', 'min:0'],
            'mobile_service' => ['nullable', 'boolean'],
            'travel_radius_km' => ['nullable', 'integer', 'min:0'],
            'country' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'document_type' => ['nullable', 'in:passport,identity_card'],
            'document' => ['nullable', 'file', 'mimes:png,jpg,pdf,jpeg', 'max:5000'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'business_name.required' => 'Le nom de l\'activité est obligatoire.',
            'country.required' => 'Le pays est obligatoire.',
            'city.required' => 'La ville est obligatoire.',
            'document.file' => 'Le document doit être un fichier valide.',
            'document.mimes' => 'Le document doit être au format png, jpg, jpeg ou pdf.',
            'document.max' => 'Le document ne doit pas dépasser 5 Mo.',
        ];
    }
}
