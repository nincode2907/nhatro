<?php

namespace App\Http\Requests\MeterReadings;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeterReadingRequest extends FormRequest
{
    private const MAX_READING = 9_999_999_999;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $readingRules = ['nullable', 'integer', 'min:0', 'max:'.self::MAX_READING];

        return [
            'electricity_previous' => $readingRules,
            'electricity_current' => $readingRules,
            'water_previous' => $readingRules,
            'water_current' => $readingRules,
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'electricity_previous' => 'chỉ số đầu kỳ điện',
            'electricity_current' => 'chỉ số mới điện',
            'water_previous' => 'chỉ số đầu kỳ nước',
            'water_current' => 'chỉ số mới nước',
            'note' => 'ghi chú',
        ];
    }
}
