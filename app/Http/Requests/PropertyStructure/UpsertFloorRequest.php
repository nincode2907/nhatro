<?php

namespace App\Http\Requests\PropertyStructure;

use App\Models\Floor;
use App\Models\Property;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertFloorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $propertyId = Property::query()
            ->where('code', config('property.code'))
            ->value('id') ?? 0;
        $floor = $this->route('floor');

        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('floors', 'code')
                    ->where(fn ($query) => $query->where('property_id', $propertyId))
                    ->ignore($floor instanceof Floor ? $floor->id : null),
            ],
            'name' => ['required', 'string', 'max:100'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:10000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'code' => 'mã tầng',
            'name' => 'tên tầng',
            'sort_order' => 'thứ tự tầng',
            'is_active' => 'trạng thái tầng',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => trim((string) $this->input('code')),
            'name' => trim((string) $this->input('name')),
        ]);
    }
}
