<?php

namespace App\Http\Requests\MeterReadings;

use App\Enums\SkipReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SkipMeterReadingRequest extends FormRequest
{
    protected $errorBag = 'skip';

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'skip_reason' => ['required', Rule::enum(SkipReason::class)],
            'note' => ['nullable', 'string', 'max:1000', 'required_if:skip_reason,'.SkipReason::Other->value],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'skip_reason.required' => 'Hãy chọn lý do bỏ qua phòng.',
            'note.required_if' => 'Hãy ghi rõ lý do khác.',
        ];
    }
}
