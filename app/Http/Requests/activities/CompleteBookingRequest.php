<?php

namespace App\Http\Requests\activities;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CompleteBookingRequest extends FormRequest
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
            'confirm_completion' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirm_completion.required' => 'La confirmation de fin de réservation est obligatoire.',
            'confirm_completion.accepted' => 'Vous devez confirmer la fin de la réservation.',
        ];
    }
}
