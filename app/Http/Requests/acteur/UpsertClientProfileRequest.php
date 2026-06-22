<?php

namespace App\Http\Requests\acteur;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class UpsertClientProfileRequest extends FormRequest
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
            'country' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'country.required' => 'Le pays est obligatoire.',
            'city.required' => 'La ville est obligatoire.',
            'latitude.numeric' => 'La latitude doit être une valeur numérique.',
            'longitude.numeric' => 'La longitude doit être une valeur numérique.',
        ];
    }
}
