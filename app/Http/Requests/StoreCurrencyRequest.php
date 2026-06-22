<?php

namespace App\Http\Requests;

use App\Models\Currency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class StoreCurrencyRequest extends FormRequest
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
        /** @var Currency|null $currency */
        $currency = $this->route('currency');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('currencies', 'name')->ignore($currency?->id)],
            'code' => ['required', 'string', 'size:3', Rule::unique('currencies', 'code')->ignore($currency?->id)],
            'symbol' => ['required', 'string', 'max:10'],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la devise est obligatoire.',
            'name.unique' => 'Cette devise existe déjà.',
            'code.required' => 'Le code de la devise est obligatoire.',
            'code.size' => 'Le code de la devise doit contenir exactement 3 caractères.',
            'code.unique' => 'Ce code devise existe déjà.',
            'symbol.required' => 'Le symbole de la devise est obligatoire.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper((string) $this->input('code')),
            ]);
        }
    }
}
