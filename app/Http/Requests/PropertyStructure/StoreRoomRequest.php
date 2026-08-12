<?php

namespace App\Http\Requests\PropertyStructure;

use App\Enums\RoomStatus;
use App\Models\Floor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $floor = $this->route('floor');
        $floorId = $floor instanceof Floor ? $floor->id : 0;

        return [
            'room_number' => [
                'required',
                'string',
                'max:30',
                Rule::unique('rooms', 'room_number')
                    ->where(fn ($query) => $query->where('floor_id', $floorId)),
            ],
            'sort_order' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'status' => ['required', Rule::enum(RoomStatus::class)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'room_number' => 'số phòng',
            'sort_order' => 'thứ tự đi',
            'status' => 'trạng thái phòng',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['room_number' => trim((string) $this->input('room_number'))]);
    }
}
