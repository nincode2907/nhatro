<?php

namespace Database\Factories;

use App\Enums\BillingPeriodStatus;
use App\Models\BillingPeriod;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingPeriod>
 */
class BillingPeriodFactory extends Factory
{
    public function definition(): array
    {
        $date = fake()->unique()->dateTimeBetween('-3 years', '+1 year');

        return [
            'property_id' => Property::factory(),
            'period_key' => $date->format('Y-m'),
            'starts_on' => $date->format('Y-m-01'),
            'ends_on' => $date->format('Y-m-t'),
            'status' => BillingPeriodStatus::Open,
        ];
    }
}
