<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'billing_period_id' => BillingPeriod::factory(),
            'room_id' => Room::factory(),
            'status' => InvoiceStatus::Draft,
            'subtotal' => 0,
            'total' => 0,
            'generated_at' => now(),
        ];
    }
}
