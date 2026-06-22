<?php

namespace App\Http\Requests\activities;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingReviewRequest extends FormRequest
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
            'review' => ['nullable', 'string', 'min:2'],
            'rating' => ['nullable', 'integer', 'between:1,5', 'required_without:review'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_id.required' => 'La réservation est obligatoire.',
            'booking_id.exists' => 'La réservation sélectionnée est invalide.',
            'review.min' => 'L\'avis doit contenir au moins 2 caractères.',
            'rating.integer' => 'La note doit être un nombre entier.',
            'rating.between' => 'La note doit être comprise entre 1 et 5.',
            'rating.required_without' => 'La note est obligatoire si aucun commentaire n\'est fourni.',
        ];
    }
}
