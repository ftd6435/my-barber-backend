<?php

namespace App\Http\Requests\user;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->user()?->id;

        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'username' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
            'telephone' => ['sometimes', 'string', 'max:255', 'regex:/^\+?[0-9]{8,15}$/u', Rule::unique('users', 'telephone')->ignore($userId)],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'current_password' => ['required_with:password', 'current_password'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'telephone.regex' => 'Le numéro de téléphone n\'est pas valide.',
            'telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'email.email' => 'L\'adresse e-mail n\'est pas valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'username.unique' => 'Ce nom d\'utilisateur est déjà utilisé.',
            'current_password.required_with' => 'Le mot de passe actuel est obligatoire pour définir un nouveau mot de passe.',
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Le nouveau mot de passe et sa confirmation ne correspondent pas.',
        ];
    }
}
