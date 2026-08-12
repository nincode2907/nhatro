<?php

namespace App\Services\Exports;

use App\Data\Exports\InvoiceExportDataset;
use App\Data\Exports\InvoiceExportRecord;
use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Models\MeterReading;
use Illuminate\Support\Collection;

class InvoiceExportDataFactory
{
    public function forPeriod(BillingPeriod $period): InvoiceExportDataset
    {
        $period->loadMissing('property');
        $previousPeriod = $period->property->billingPeriods()
            ->where('period_key', $period->starts_on->copy()->subMonthNoOverflow()->format('Y-m'))
            ->first();

        return new InvoiceExportDataset(
            $period,
            $this->recordsForPeriod($period),
            $previousPeriod,
            $previousPeriod ? $this->recordsForPeriod($previousPeriod) : collect(),
        );
    }

    /** @return Collection<int, InvoiceExportRecord> */
    private function recordsForPeriod(BillingPeriod $period): Collection
    {
        $period->loadMissing('property');
        $invoices = $period->invoices()
            ->with(['billingPeriod.property', 'room.floor', 'items'])
            ->get()
            ->sortBy([
                ['room.floor.sort_order', 'asc'],
                ['room.sort_order', 'asc'],
                ['room.room_number', 'asc'],
            ])
            ->values();
        $readings = $period->meterReadings()
            ->whereIn('room_id', $invoices->pluck('room_id'))
            ->get()
            ->keyBy('room_id');

        $records = $invoices->map(
            fn (Invoice $invoice): InvoiceExportRecord => new InvoiceExportRecord(
                $invoice,
                $readings->get($invoice->room_id),
            ),
        );

        return $records;
    }

    public function forInvoice(Invoice $invoice): InvoiceExportRecord
    {
        $invoice->loadMissing(['billingPeriod.property', 'room.floor', 'items']);
        $reading = MeterReading::query()
            ->where('billing_period_id', $invoice->billing_period_id)
            ->where('room_id', $invoice->room_id)
            ->first();

        return new InvoiceExportRecord($invoice, $reading);
    }
}
