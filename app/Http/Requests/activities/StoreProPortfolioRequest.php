<?php

namespace App\Http\Requests\activities;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProPortfolioRequest extends FormRequest
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
        $imageRule = $this->route('proPortfolio')
            ? ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']
            : ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];

        return [
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'image' => $imageRule,
        ];
    }

    public function messages(): array
    {
        return [
            'service_id.exists' => 'Le service sélectionné est invalide.',
            'image.required' => 'L\'image du portfolio est obligatoire.',
            'image.image' => 'Le fichier doit être une image valide.',
            'image.mimes' => 'L\'image doit être au format jpg, jpeg, png ou webp.',
            'image.max' => 'L\'image ne doit pas dépasser 5 Mo.',
        ];
    }
}
