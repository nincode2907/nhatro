<?php

namespace Database\Factories;

use App\Enums\MeterReadingStatus;
use App\Models\BillingPeriod;
use App\Models\MeterReading;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeterReading>
 */
class MeterReadingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'billing_period_id' => BillingPeriod::factory(),
            'room_id' => Room::factory(),
            'status' => MeterReadingStatus::Pending,
            'meter_reset' => false,
        ];
    }

    public function recorded(
        int $electricityPrevious = 1000,
        int $electricityCurrent = 1100,
        int $waterPrevious = 100,
        int $waterCurrent = 105,
    ): static {
        return $this->state(fn (): array => [
            'status' => MeterReadingStatus::Recorded,
            'electricity_previous' => $electricityPrevious,
            'electricity_current' => $electricityCurrent,
            'electricity_usage' => $electricityCurrent - $electricityPrevious,
            'water_previous' => $waterPrevious,
            'water_current' => $waterCurrent,
            'water_usage' => $waterCurrent - $waterPrevious,
            'recorded_at' => now(),
        ]);
    }
}
