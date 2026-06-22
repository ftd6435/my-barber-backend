<?php

namespace App\Http\Requests\auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class LoginRequest extends FormRequest
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
            'login' => ['required', 'string'],
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'login.required' => 'L\'identifiant (email ou téléphone) est obligatoire.',
            'password.min' => 'Le mot de passe doit comporter au moins 8 caractères.',
        ];
    }
}
