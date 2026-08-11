<?php

namespace App\Http\Requests\Rooms;

use App\Enums\RoomStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomSettingsRequest extends FormRequest
{
    private const MAX_MONEY_AMOUNT = 1_000_000_000;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $moneyRules = ['required', 'integer', 'min:0', 'max:'.self::MAX_MONEY_AMOUNT];

        return [
            'status' => ['required', Rule::enum(RoomStatus::class)],
            'sort_order' => ['required', 'integer', 'min:1', 'max:10000'],
            'room_note' => ['nullable', 'string', 'max:1000'],
            'rent_amount' => $moneyRules,
            'electricity_unit_price' => $moneyRules,
            'water_unit_price' => $moneyRules,
            'vehicle_amount' => $moneyRules,
            'garbage_amount' => $moneyRules,
            'cable_amount' => $moneyRules,
            'other_amount' => $moneyRules,
            'electricity_enabled' => ['required', 'boolean'],
            'water_enabled' => ['required', 'boolean'],
            'settings_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'status' => 'trạng thái',
            'sort_order' => 'thứ tự đi',
            'room_note' => 'ghi chú phòng',
            'rent_amount' => 'tiền phòng',
            'electricity_unit_price' => 'đơn giá điện',
            'water_unit_price' => 'đơn giá nước',
            'vehicle_amount' => 'phí xe',
            'garbage_amount' => 'phí rác',
            'cable_amount' => 'phí cáp / Internet',
            'other_amount' => 'khoản cố định khác',
            'electricity_enabled' => 'trạng thái đồng hồ điện',
            'water_enabled' => 'trạng thái đồng hồ nước',
            'settings_note' => 'ghi chú cấu hình',
        ];
    }
}
