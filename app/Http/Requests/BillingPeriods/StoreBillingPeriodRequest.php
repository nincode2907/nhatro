<?php

namespace App\Http\Requests\BillingPeriods;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillingPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'period_key' => ['required', 'date_format:Y-m'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'period_key.required' => 'Hãy chọn tháng cần ghi điện nước.',
            'period_key.date_format' => 'Tháng đã chọn không hợp lệ.',
        ];
    }
}
