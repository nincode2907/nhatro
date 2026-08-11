<?php

namespace App\Actions\MeterReadings;

use App\Enums\MeterReadingStatus;
use App\Models\BillingPeriod;
use App\Models\MeterReading;
use App\Models\Room;

class EnsureMeterReading
{
    public function handle(BillingPeriod $period, Room $room): MeterReading
    {
        $existing = MeterReading::query()
            ->whereBelongsTo($period)
            ->whereBelongsTo($room)
            ->first();

        if ($existing) {
            return $existing;
        }

        return MeterReading::query()->firstOrCreate(
            [
                'billing_period_id' => $period->id,
                'room_id' => $room->id,
            ],
            [
                'status' => MeterReadingStatus::Pending,
                'electricity_previous' => $this->latestPreviousValue($period, $room, 'electricity_current'),
                'water_previous' => $this->latestPreviousValue($period, $room, 'water_current'),
                'meter_reset' => false,
            ],
        );
    }

    private function latestPreviousValue(BillingPeriod $period, Room $room, string $column): ?int
    {
        $value = MeterReading::query()
            ->select("meter_readings.{$column}")
            ->join('billing_periods', 'billing_periods.id', '=', 'meter_readings.billing_period_id')
            ->where('meter_readings.room_id', $room->id)
            ->where('meter_readings.status', MeterReadingStatus::Recorded->value)
            ->whereNotNull("meter_readings.{$column}")
            ->where('billing_periods.property_id', $period->property_id)
            ->where('billing_periods.starts_on', '<', $period->starts_on)
            ->orderByDesc('billing_periods.starts_on')
            ->orderByDesc('meter_readings.id')
            ->value("meter_readings.{$column}");

        return $value === null ? null : (int) $value;
    }
}
