<?php

namespace App\Data\Exports;

use App\Enums\InvoiceItemType;
use App\Models\BillingPeriod;
use Illuminate\Support\Collection;
use OverflowException;

class InvoiceExportDataset
{
    /**
     * @param  Collection<int, InvoiceExportRecord>  $records
     * @param  Collection<int, InvoiceExportRecord>  $previousRecords
     */
    public function __construct(
        public readonly BillingPeriod $period,
        public readonly Collection $records,
        public readonly ?BillingPeriod $previousPeriod,
        public readonly Collection $previousRecords,
    ) {}

    public function grandTotal(): int
    {
        return $this->sumRecords($this->records);
    }

    public function previousGrandTotal(): int
    {
        return $this->sumRecords($this->previousRecords);
    }

    public function totalFor(InvoiceItemType $type): int
    {
        return $this->sumRecords($this->records, $type);
    }

    public function previousTotalFor(InvoiceItemType $type): int
    {
        return $this->sumRecords($this->previousRecords, $type);
    }

    /** @param Collection<int, InvoiceExportRecord> $records */
    private function sumRecords(Collection $records, ?InvoiceItemType $type = null): int
    {
        $total = 0;

        foreach ($records as $record) {
            $amount = $type ? $record->amount($type) : $record->invoice->total;

            if ($amount > PHP_INT_MAX - $total) {
                throw new OverflowException('Tổng kỳ vượt giới hạn số nguyên của hệ thống.');
            }

            $total += $amount;
        }

        return $total;
    }
}
