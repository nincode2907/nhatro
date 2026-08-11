<?php

namespace Database\Factories;

use App\Enums\RoomStatus;
use App\Models\Floor;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'floor_id' => Floor::factory(),
            'room_number' => (string) fake()->unique()->numberBetween(100, 9999),
            'sort_order' => fake()->numberBetween(1, 100),
            'status' => RoomStatus::Occupied,
            'note' => null,
            'is_active' => true,
        ];
    }
}
