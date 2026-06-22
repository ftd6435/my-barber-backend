<?php

namespace App\Http\Requests\activities;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class RejectBookingRequest extends FormRequest
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
            'professionel_comment' => ['required', 'string', 'min:2'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'professionel_comment.required' => 'Le commentaire du professionnel est obligatoire.',
            'professionel_comment.min' => 'Le commentaire du professionnel doit contenir au moins 2 caractères.',
        ];
    }
}
