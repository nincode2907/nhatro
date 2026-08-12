<?php

namespace App\Actions\MeterReadings;

use App\Models\BillingPeriod;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;

class ResetBillingPeriodReadings
{
    /** @return array{readings: int, invoices: int} */
    public function handle(BillingPeriod $period): array
    {
        return DB::transaction(function () use ($period): array {
            $invoiceIds = $period->invoices()->pluck('id');

            if ($invoiceIds->isNotEmpty()) {
                InvoiceItem::query()->whereIn('invoice_id', $invoiceIds)->delete();
            }

            $invoices = $period->invoices()->delete();
            $readings = $period->meterReadings()->delete();

            return compact('readings', 'invoices');
        });
    }
}
