<?php

namespace App\Actions\BillingPeriods;

use App\Actions\MeterReadings\ReadingFlow;
use App\Enums\BillingPeriodStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingStatus;
use App\Models\BillingPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizeBillingPeriod
{
    public function __construct(private readonly ReadingFlow $readingFlow) {}

    public function handle(BillingPeriod $period, User $user): BillingPeriod
    {
        return DB::transaction(function () use ($period, $user): BillingPeriod {
            $period = BillingPeriod::query()->lockForUpdate()->findOrFail($period->id);

            if (! $period->isOpen()) {
                throw ValidationException::withMessages(['period' => 'Kỳ này đã được đóng.']);
            }

            $pendingCount = $period->property->floors()
                ->where('is_active', true)
                ->get()
                ->sum(function ($floor) use ($period): int {
                    $rooms = $this->readingFlow->roomsFor($floor);
                    $statuses = $this->readingFlow->statusesFor($period, $rooms);

                    return $statuses->filter(
                        fn (MeterReadingStatus $status): bool => $status === MeterReadingStatus::Pending,
                    )->count();
                });

            if ($pendingCount > 0) {
                throw ValidationException::withMessages([
                    'period' => "Chưa thể đóng kỳ vì còn {$pendingCount} phòng chưa ghi hoặc bỏ qua.",
                ]);
            }

            $finalizedAt = now();

            $period->invoices()
                ->where('status', InvoiceStatus::Draft->value)
                ->update([
                    'status' => InvoiceStatus::Finalized->value,
                    'locked_at' => $finalizedAt,
                ]);

            $period->forceFill([
                'status' => BillingPeriodStatus::Finalized,
                'finalized_by' => $user->id,
                'finalized_at' => $finalizedAt,
            ])->save();

            return $period->refresh();
        });
    }
}
