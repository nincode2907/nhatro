<?php

namespace App\Services\Exports;

use App\Data\Exports\InvoiceExportDataset;
use App\Data\Exports\InvoiceExportRecord;
use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Models\MeterReading;

class InvoiceExportDataFactory
{
    public function forPeriod(BillingPeriod $period): InvoiceExportDataset
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

        return new InvoiceExportDataset($period, $records);
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
