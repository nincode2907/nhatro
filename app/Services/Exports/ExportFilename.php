<?php

namespace App\Services\Exports;

use App\Models\BillingPeriod;
use App\Models\Invoice;
use Illuminate\Support\Str;

class ExportFilename
{
    public function monthly(BillingPeriod $period, string $extension): string
    {
        return implode('_', [
            $this->safePart($period->property->code, 'property'),
            $period->period_key,
            'hoa-don',
        ]).'.'.$extension;
    }

    public function single(Invoice $invoice, string $extension): string
    {
        return implode('_', [
            $this->safePart($invoice->billingPeriod->property->code, 'property'),
            $invoice->billingPeriod->period_key,
            'phong-'.$this->safePart($invoice->room->room_number, (string) $invoice->room_id),
        ]).'.'.$extension;
    }

    private function safePart(string $value, string $fallback): string
    {
        return Str::slug($value) ?: $fallback;
    }
}
