<?php

namespace Database\Factories;

use App\Enums\InvoiceItemType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'type' => InvoiceItemType::Rent,
            'description' => 'Tiền phòng',
            'quantity' => 1,
            'unit' => 'tháng',
            'unit_price' => 3_000_000,
            'amount' => 3_000_000,
            'metadata' => null,
            'sort_order' => 10,
        ];
    }
}
