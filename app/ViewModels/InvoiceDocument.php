<?php

namespace App\ViewModels;

use App\Enums\InvoiceItemType;
use App\Models\Invoice;
use App\Models\InvoiceItem;

class InvoiceDocument
{
    public function __construct(public readonly Invoice $invoice)
    {
        $invoice->loadMissing(['billingPeriod.property', 'room.floor', 'items']);
    }

    /**
     * @return list<array{type: InvoiceItemType, label: string, item: InvoiceItem|null}>
     */
    public function rows(): array
    {
        $items = $this->invoice->items->keyBy(
            fn (InvoiceItem $item): string => $item->type->value,
        );

        return array_map(
            fn (InvoiceItemType $type): array => [
                'type' => $type,
                'label' => $type->label(),
                'item' => $items->get($type->value),
            ],
            InvoiceItemType::cases(),
        );
    }
}
