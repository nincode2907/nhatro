<?php

namespace App\Http\Requests\PropertyStructure;

use App\Enums\RoomStatus;
use App\Models\Property;
use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomStructureRequest extends FormRequest
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
        $room = $this->route('room');
        $roomId = $room instanceof Room ? $room->id : null;
        $floorId = (int) $this->input('floor_id');

        return [
            'floor_id' => [
                'required',
                'integer',
                Rule::exists('floors', 'id')->where('property_id', $propertyId),
            ],
            'room_number' => [
                'required',
                'string',
                'max:30',
                Rule::unique('rooms', 'room_number')
                    ->where(fn ($query) => $query->where('floor_id', $floorId))
                    ->ignore($roomId),
            ],
            'sort_order' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'status' => ['required', Rule::enum(RoomStatus::class)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'floor_id' => 'tầng',
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
