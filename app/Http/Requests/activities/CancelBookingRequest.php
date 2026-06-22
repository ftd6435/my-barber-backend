<?php

namespace App\Http\Requests\activities;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class CancelBookingRequest extends FormRequest
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
            'cancel_reason' => ['required', 'string', 'min:2'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'cancel_reason.required' => 'Le motif d\'annulation est obligatoire.',
            'cancel_reason.min' => 'Le motif d\'annulation doit contenir au moins 2 caractères.',
        ];
    }
}
