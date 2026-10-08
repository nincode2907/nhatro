<?php

namespace App\Http\Requests\Properties;

use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDefaultPricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (array_keys(Money::DEFAULT_PRICES) as $field) {
            if ($this->has($field)) {
                $this->merge([$field => Money::normalize($this->input($field))]);
            }
        }
    }

    public function rules(): array
    {
        return array_fill_keys(array_keys(Money::DEFAULT_PRICES), ['required', 'integer', 'min:0', 'max:1000000000']);
    }
}
