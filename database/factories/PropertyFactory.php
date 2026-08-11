<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('PROP-###'),
            'name' => fake()->company(),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}
