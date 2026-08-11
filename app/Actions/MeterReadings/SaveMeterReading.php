<?php

namespace App\Actions\MeterReadings;

use App\Enums\BillingPeriodStatus;
use App\Enums\MeterReadingStatus;
use App\Models\BillingPeriod;
use App\Models\Floor;
use App\Models\MeterReading;
use App\Models\Room;
use App\Services\Billing\InvoiceCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveMeterReading
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
                    'period' => 'Kỳ này đã chốt nên không thể sửa chỉ số.',
                ]);
            }

            $room->loadMissing('settings');
            $reading = $this->ensureMeterReading->handle($period, $room);
            $values = $this->validatedMeterValues($reading, $room, $data);

            $reading->update([
                ...$values,
                'status' => MeterReadingStatus::Recorded,
                'skip_reason' => null,
                'note' => $data['note'] ?? null,
                'meter_reset' => false,
                'recorded_at' => now(),
            ]);

            $this->invoiceCalculator->recalculateDraft($period, $room, $reading->refresh());

            return $this->readingFlow->nextUnprocessed($period, $floor, $room);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, int|null>
     */
    private function validatedMeterValues(MeterReading $reading, Room $room, array $data): array
    {
        $errors = [];
        $values = [];

        foreach (['electricity', 'water'] as $meter) {
            $enabled = (bool) ($room->settings?->{$meter.'_enabled'} ?? false);
            $previousKey = $meter.'_previous';
            $currentKey = $meter.'_current';
            $usageKey = $meter.'_usage';

            if (! $enabled) {
                $values[$previousKey] = null;
                $values[$currentKey] = null;
                $values[$usageKey] = null;

                continue;
            }

            $storedPrevious = $reading->{$previousKey};
            $submittedPrevious = $data[$previousKey] ?? null;
            $previous = $storedPrevious ?? $submittedPrevious;
            $current = $data[$currentKey] ?? null;
            $label = $meter === 'electricity' ? 'điện' : 'nước';

            if ($storedPrevious !== null && $submittedPrevious !== null && (int) $submittedPrevious !== $storedPrevious) {
                $errors[$previousKey] = "Chỉ số cũ {$label} đã được hệ thống ghi nhận và không thể thay đổi tại đây.";
            } elseif ($previous === null) {
                $errors[$previousKey] = "Hãy nhập chỉ số đầu kỳ {$label} vì chưa có dữ liệu kỳ trước.";
            }

            if ($current === null) {
                $errors[$currentKey] = "Hãy nhập chỉ số mới {$label}.";
            }

            if ($previous !== null && $current !== null && (int) $current < (int) $previous) {
                $errors[$currentKey] = 'Chỉ số mới nhỏ hơn chỉ số cũ. Vui lòng kiểm tra lại.';
            }

            if (! isset($errors[$previousKey]) && ! isset($errors[$currentKey])) {
                $values[$previousKey] = (int) $previous;
                $values[$currentKey] = (int) $current;
                $values[$usageKey] = (int) $current - (int) $previous;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $values;
    }
}
