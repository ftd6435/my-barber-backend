<?php

namespace App\Http\Requests\activities;

use App\Models\Activities\AgeRange;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class StoreAgeRangeRequest extends FormRequest
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
        /** @var AgeRange|null $ageRange */
        $ageRange = $this->route('ageRange');

        return [
            'name' => ['required', 'string', 'max:160', Rule::unique('age_ranges', 'name')->ignore($ageRange?->id)],
            'description' => ['nullable', 'string'],
            'min_age' => ['nullable', 'integer', 'min:0'],
            'max_age' => ['nullable', 'integer', 'min:0', 'gte:min_age'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la tranche d\'âge est obligatoire.',
            'name.unique' => 'Cette tranche d\'âge existe déjà.',
            'min_age.integer' => 'L\'âge minimum doit être un nombre entier.',
            'min_age.min' => 'L\'âge minimum doit être supérieur ou égal à 0.',
            'max_age.integer' => 'L\'âge maximum doit être un nombre entier.',
            'max_age.min' => 'L\'âge maximum doit être supérieur ou égal à 0.',
            'max_age.gte' => 'L\'âge maximum doit être supérieur ou égal à l\'âge minimum.',
        ];
    }
}
