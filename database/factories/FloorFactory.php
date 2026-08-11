<?php

namespace Database\Factories;

use App\Models\Floor;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Floor>
 */
class FloorFactory extends Factory
{
    public function definition(): array
    {
        $number = fake()->numberBetween(1, 20);

        return [
            'property_id' => Property::factory(),
            'code' => (string) $number,
            'name' => "Tầng {$number}",
            'sort_order' => $number,
            'is_active' => true,
        ];
    }
}
