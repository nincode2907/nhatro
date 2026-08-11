<?php

namespace App\Actions\MeterReadings;

use App\Enums\BillingPeriodStatus;
use App\Enums\MeterReadingStatus;
use App\Models\BillingPeriod;
use App\Models\Floor;
use App\Models\Room;
use App\Services\Billing\InvoiceCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SkipMeterReading
{
    public function __construct(
        private readonly EnsureMeterReading $ensureMeterReading,
        private readonly ReadingFlow $readingFlow,
        private readonly InvoiceCalculator $invoiceCalculator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(BillingPeriod $period, Floor $floor, Room $room, array $data): ?Room
    {
        return DB::transaction(function () use ($period, $floor, $room, $data): ?Room {
            $period = BillingPeriod::query()->lockForUpdate()->findOrFail($period->id);

            if ($period->status !== BillingPeriodStatus::Open) {
                throw ValidationException::withMessages([
                    'period' => 'Kỳ này đã chốt nên không thể bỏ qua phòng.',
                ]);
            }

            $reading = $this->ensureMeterReading->handle($period, $room);
            $reading->update([
                'status' => MeterReadingStatus::Skipped,
                'electricity_current' => null,
                'electricity_usage' => null,
                'water_current' => null,
                'water_usage' => null,
                'skip_reason' => $data['skip_reason'],
                'note' => $data['note'] ?? null,
                'meter_reset' => false,
                'recorded_at' => null,
            ]);

            $this->invoiceCalculator->recalculateDraft($period, $room, $reading->refresh());

            return $this->readingFlow->nextUnprocessed($period, $floor, $room);
        });
    }
}
