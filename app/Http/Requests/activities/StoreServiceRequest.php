<?php

namespace App\Http\Requests\activities;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
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
            'salon_id' => ['required', 'integer', 'exists:salons,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'prices' => ['nullable', 'array'],
            'prices.*.age_range_id' => ['required_with:prices', 'integer', 'exists:age_ranges,id', 'distinct'],
            'prices.*.price' => ['required_with:prices', 'numeric', 'min:0'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'salon_id.required' => 'Le salon est obligatoire.',
            'salon_id.exists' => 'Le salon sélectionné est invalide.',
            'category_id.required' => 'La catégorie est obligatoire.',
            'category_id.exists' => 'La catégorie sélectionnée est invalide.',
            'name.required' => 'Le nom du service est obligatoire.',
            'duration_minutes.required' => 'La durée du service est obligatoire.',
            'duration_minutes.integer' => 'La durée doit être un nombre entier de minutes.',
            'prices.*.age_range_id.required_with' => 'La tranche d\'âge est obligatoire pour chaque prix.',
            'prices.*.age_range_id.exists' => 'La tranche d\'âge sélectionnée est invalide.',
            'prices.*.age_range_id.distinct' => 'Une tranche d\'âge ne peut être utilisée qu\'une seule fois par service.',
            'prices.*.price.required_with' => 'Le prix est obligatoire pour chaque tranche d\'âge.',
            'prices.*.price.numeric' => 'Le prix doit être un nombre valide.',
            'images.*.image' => 'Chaque fichier du portfolio doit être une image valide.',
            'images.*.mimes' => 'Les images du portfolio doivent être au format jpg, jpeg, png ou webp.',
            'images.*.max' => 'Chaque image du portfolio ne doit pas dépasser 5 Mo.',
        ];
    }
}
