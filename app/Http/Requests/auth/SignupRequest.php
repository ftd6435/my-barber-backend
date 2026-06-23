<?php

namespace App\Http\Requests\auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class SignupRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'telephone' => ['required', 'string', 'max:255', 'regex:/^\+?[0-9]{8,15}$/u', 'unique:users,telephone'],
            'email' => ['required', 'string', 'email:255', 'max:255', 'unique:users,email'],
            'default_currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'first_name.required' => "Le prenom est obligatoire",
            'first_name.string' => "Le prenom doit être une chaine de caractères",
            'first_name.max' => "Le prenom ne peut pas dépasser :max caractères",
            'last_name.required' => "Le nom est obligatoire",
            'last_name.string' => "Le nom doit être une chaine de caractères",
            'last_name.max' => "Le nom ne peut pas dépasser :max caractères",
            'telephone.required' => "Le telephone est obligatoire",
            'telephone.string' => "Le telephone doit être une chaine de caractères",
            'telephone.max' => "Le telephone ne peut pas dépasser :max caractères",
            'email.required' => "L'e-mail est obligatoire",
            'email.string' => "L'e-mail doit être une chaine de caractères",
            'email.max' => "L'e-mail ne peut pas dépasser :max caractères",
            'email.email' => "L'e-mail n'est pas valide",
            'default_currency_id.required' => "La devise par défaut est obligatoire",
            'default_currency_id.exists' => "La devise par défaut sélectionnée est invalide",
            'password.required' => "Le mot de passe est obligatoire",
            'password.confirmed' => "Le mot de passe et la confirmation ne correspondent pas",
        ];
    }
}
