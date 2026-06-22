<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class StoreSalonRequest extends FormRequest
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
        $salonUuid = $this->route('uuid');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'salon_phone' => ['required', 'string', 'max:255', Rule::unique('salons', 'salon_phone')->ignore($salonUuid, 'uuid')],
            'salon_email' => ['required', 'string', 'max:255', Rule::unique('salons', 'salon_email')->ignore($salonUuid, 'uuid')],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:5000'],
            'banner' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:5000'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du salon est obligatoire.',
            'description.required' => 'La description du salon est obligatoire.',
            'address.required' => 'L\'adresse du salon est obligatoire.',
            'salon_phone.required' => 'Le téléphone du salon est obligatoire.',
            'salon_phone.unique' => 'Ce téléphone de salon est déjà utilisé.',
            'salon_email.required' => 'L\'adresse e-mail du salon est obligatoire.',
            'salon_email.unique' => 'Cette adresse e-mail de salon est déjà utilisée.',
            'latitude.required' => 'La latitude est obligatoire.',
            'longitude.required' => 'La longitude est obligatoire.',
            'logo.image' => 'Le logo doit être une image valide.',
            'logo.mimes' => 'Le logo doit être au format png, jpg ou jpeg.',
            'logo.max' => 'Le logo ne doit pas dépasser 5 Mo.',
            'banner.image' => 'La bannière doit être une image valide.',
            'banner.mimes' => 'La bannière doit être au format png, jpg ou jpeg.',
            'banner.max' => 'La bannière ne doit pas dépasser 5 Mo.',
        ];
    }
}
