<?php

namespace App\Data\Exports;

use App\Enums\InvoiceItemType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MeterReading;
use App\ViewModels\InvoiceDocument;

class InvoiceExportRecord
{
    public readonly InvoiceDocument $document;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly ?MeterReading $reading,
    ) {
        $this->document = new InvoiceDocument($invoice);
    }

    public function item(InvoiceItemType $type): ?InvoiceItem
    {
        return $this->document->item($type);
    }

    public function amount(InvoiceItemType $type): int
    {
        return $this->item($type)?->amount ?? 0;
    }

    public function unitPrice(InvoiceItemType $type): int
    {
        return $this->item($type)?->unit_price ?? 0;
    }

    public function quantity(InvoiceItemType $type): ?int
    {
        return $this->item($type)?->quantity;
    }

    public function previous(InvoiceItemType $type): ?int
    {
        $metadata = $this->item($type)?->metadata;
        $value = $metadata['previous'] ?? null;

        return $value === null ? null : (int) $value;
    }

    public function current(InvoiceItemType $type): ?int
    {
        $metadata = $this->item($type)?->metadata;
        $value = $metadata['current'] ?? null;

        return $value === null ? null : (int) $value;
    }

    public function statusLabel(): string
    {
        return $this->reading?->status->label() ?? $this->invoice->status->label();
    }

    public function note(): ?string
    {
        return $this->reading?->note ?: $this->invoice->note;
    }
}
