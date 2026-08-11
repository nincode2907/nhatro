<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\RoomSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomSetting>
 */
class RoomSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'rent_amount' => 3_000_000,
            'electricity_unit_price' => 3_200,
            'water_unit_price' => 17_000,
            'vehicle_amount' => 120_000,
            'garbage_amount' => 30_000,
            'cable_amount' => 0,
            'other_amount' => 0,
            'electricity_enabled' => true,
            'water_enabled' => true,
            'note' => null,
        ];
    }
}
