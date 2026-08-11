<?php

namespace App\Data\Exports;

use App\Models\BillingPeriod;
use Illuminate\Support\Collection;
use OverflowException;

class InvoiceExportDataset
{
    /**
     * @param  Collection<int, InvoiceExportRecord>  $records
     */
    public function __construct(
        public readonly BillingPeriod $period,
        public readonly Collection $records,
    ) {}

    public function grandTotal(): int
    {
        $total = 0;

        foreach ($this->records as $record) {
            if ($record->invoice->total > PHP_INT_MAX - $total) {
                throw new OverflowException('Tổng kỳ vượt giới hạn số nguyên của hệ thống.');
            }

            $total += $record->invoice->total;
        }

        return $total;
    }
}
