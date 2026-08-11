<?php

namespace App\Services\Billing;

use App\Enums\BillingPeriodStatus;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingStatus;
use App\Enums\RoomStatus;
use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use LogicException;
use OverflowException;

class InvoiceCalculator
{
    public function recalculateDraft(BillingPeriod $period, Room $room, MeterReading $reading): Invoice
    {
        return DB::transaction(function () use ($period, $room, $reading): Invoice {
            $period = BillingPeriod::query()->lockForUpdate()->findOrFail($period->id);
            $room->loadMissing(['floor', 'settings']);

            $this->ensureRelated($period, $room, $reading);

            $invoice = Invoice::query()
                ->whereBelongsTo($period)
                ->whereBelongsTo($room)
                ->lockForUpdate()
                ->first();

            if ($invoice?->status === InvoiceStatus::Finalized) {
                return $invoice->load('items');
            }

            if ($period->status !== BillingPeriodStatus::Open) {
                throw new LogicException('Chỉ có thể tính hóa đơn nháp trong kỳ đang mở.');
            }

            if (! in_array($reading->status, [MeterReadingStatus::Recorded, MeterReadingStatus::Skipped], true)) {
                throw new LogicException('Chỉ số phải được ghi hoặc bỏ qua trước khi tính hóa đơn nháp.');
            }

            $calculation = $this->calculate($period, $room, $reading);
            $invoice ??= new Invoice([
                'billing_period_id' => $period->id,
                'room_id' => $room->id,
            ]);
            $invoice->fill([
                'status' => InvoiceStatus::Draft,
                'subtotal' => $calculation['total'],
                'total' => $calculation['total'],
                'generated_at' => now(),
                'locked_at' => null,
            ])->save();

            $invoice->items()->delete();
            $invoice->items()->createMany($calculation['items']);

            return $invoice->load('items');
        });
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    private function calculate(BillingPeriod $period, Room $room, MeterReading $reading): array
    {
        if ($room->status !== RoomStatus::Occupied) {
            return ['items' => [], 'total' => 0];
        }

        $settings = $room->settings;

        if (! $settings) {
            throw new LogicException("Phòng {$room->room_number} chưa có cấu hình giá.");
        }

        $items = [];
        $sortOrder = 10;

        $items[] = $this->fixedItem(
            InvoiceItemType::Rent,
            'Tiền phòng',
            $settings->rent_amount,
            $sortOrder,
        );
        $sortOrder += 10;

        if ($reading->status === MeterReadingStatus::Recorded && $settings->electricity_enabled) {
            $items[] = $this->meterItem(
                InvoiceItemType::Electricity,
                'Điện '.$period->label(),
                'kWh',
                $reading->electricity_previous,
                $reading->electricity_current,
                $reading->electricity_usage,
                $settings->electricity_unit_price,
                $sortOrder,
            );
            $sortOrder += 10;
        }

        if ($reading->status === MeterReadingStatus::Recorded && $settings->water_enabled) {
            $items[] = $this->meterItem(
                InvoiceItemType::Water,
                'Nước '.$period->label(),
                'm³',
                $reading->water_previous,
                $reading->water_current,
                $reading->water_usage,
                $settings->water_unit_price,
                $sortOrder,
            );
            $sortOrder += 10;
        }

        foreach ([
            [InvoiceItemType::Vehicle, 'Xe', $settings->vehicle_amount],
            [InvoiceItemType::Garbage, 'Rác', $settings->garbage_amount],
            [InvoiceItemType::Cable, 'Cáp / Internet', $settings->cable_amount],
            [InvoiceItemType::Other, 'Khoản khác', $settings->other_amount],
        ] as [$type, $description, $amount]) {
            if ($amount > 0) {
                $items[] = $this->fixedItem($type, $description, $amount, $sortOrder);
                $sortOrder += 10;
            }
        }

        $total = 0;

        foreach ($items as $item) {
            $total = $this->safeAdd($total, $item['amount']);
        }

        return ['items' => $items, 'total' => $total];
    }

    /** @return array<string, mixed> */
    private function fixedItem(
        InvoiceItemType $type,
        string $description,
        int $amount,
        int $sortOrder,
    ): array {
        if ($amount < 0) {
            throw new LogicException("Khoản {$description} không được âm.");
        }

        return [
            'type' => $type,
            'description' => $description,
            'quantity' => 1,
            'unit' => 'tháng',
            'unit_price' => $amount,
            'amount' => $amount,
            'metadata' => null,
            'sort_order' => $sortOrder,
        ];
    }

    /** @return array<string, mixed> */
    private function meterItem(
        InvoiceItemType $type,
        string $description,
        string $unit,
        ?int $previous,
        ?int $current,
        ?int $quantity,
        int $unitPrice,
        int $sortOrder,
    ): array {
        if ($previous === null || $current === null || $quantity === null) {
            throw new LogicException("Thiếu dữ liệu {$description} để tính hóa đơn.");
        }

        return [
            'type' => $type,
            'description' => $description,
            'quantity' => $quantity,
            'unit' => $unit,
            'unit_price' => $unitPrice,
            'amount' => $this->safeMultiply($quantity, $unitPrice),
            'metadata' => [
                'previous' => $previous,
                'current' => $current,
            ],
            'sort_order' => $sortOrder,
        ];
    }

    private function safeMultiply(int $quantity, int $unitPrice): int
    {
        if ($quantity < 0 || $unitPrice < 0) {
            throw new LogicException('Số lượng và đơn giá hóa đơn không được âm.');
        }

        if ($unitPrice !== 0 && $quantity > intdiv(PHP_INT_MAX, $unitPrice)) {
            throw new OverflowException('Thành tiền vượt giới hạn số nguyên của hệ thống.');
        }

        return $quantity * $unitPrice;
    }

    private function safeAdd(int $total, int $amount): int
    {
        if ($amount > PHP_INT_MAX - $total) {
            throw new OverflowException('Tổng hóa đơn vượt giới hạn số nguyên của hệ thống.');
        }

        return $total + $amount;
    }

    private function ensureRelated(BillingPeriod $period, Room $room, MeterReading $reading): void
    {
        $related = $room->floor->property_id === $period->property_id
            && $reading->billing_period_id === $period->id
            && $reading->room_id === $room->id;

        if (! $related) {
            throw new LogicException('Kỳ, phòng và chỉ số không thuộc cùng một hóa đơn.');
        }
    }
}
