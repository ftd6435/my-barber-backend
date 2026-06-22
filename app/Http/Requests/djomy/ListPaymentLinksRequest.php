<?php

namespace App\Http\Requests\djomy;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ListPaymentLinksRequest extends FormRequest
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
            'page' => ['nullable', 'integer', 'min:0'],
            'size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'startDate' => ['nullable', 'date_format:Y-m-d\TH:i:s'],
            'endDate' => ['nullable', 'date_format:Y-m-d\TH:i:s'],
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer' => 'La page doit être un nombre entier.',
            'page.min' => 'La page doit être supérieure ou égale à 0.',
            'size.integer' => 'La taille de page doit être un nombre entier.',
            'size.min' => 'La taille de page doit être supérieure à zéro.',
            'size.max' => 'La taille de page ne doit pas dépasser 100.',
            'startDate.date_format' => 'La date de début doit respecter le format attendu.',
            'endDate.date_format' => 'La date de fin doit respecter le format attendu.',
        ];
    }
}
