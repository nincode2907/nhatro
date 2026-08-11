<?php

namespace Tests\Feature;

use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\RoomStatus;
use App\Models\BillingPeriod;
use App\Models\Floor;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomSetting;
use App\Models\User;
use App\Services\Billing\InvoiceCalculator;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceBillingTest extends TestCase
{
    use RefreshDatabase;

    private Property $property;

    private Floor $floor;

    private Room $room;

    private RoomSetting $settings;

    private BillingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('property.code', 'TEST-HOUSE');
        $this->property = Property::factory()->create(['code' => 'TEST-HOUSE']);
        $this->floor = Floor::factory()->for($this->property)->create([
            'name' => 'Tầng 1',
            'sort_order' => 1,
        ]);
        $this->room = Room::factory()->for($this->floor)->create([
            'room_number' => '101',
            'sort_order' => 1,
        ]);
        $this->settings = RoomSetting::factory()->for($this->room)->create([
            'rent_amount' => 3_200_000,
            'electricity_unit_price' => 3_200,
            'water_unit_price' => 17_000,
            'vehicle_amount' => 120_000,
            'garbage_amount' => 30_000,
            'cable_amount' => 50_000,
            'other_amount' => 25_000,
        ]);
        $this->period = BillingPeriod::factory()->for($this->property)->create([
            'period_key' => '2026-08',
            'starts_on' => '2026-08-01',
            'ends_on' => '2026-08-31',
        ]);
    }

    public function test_calculator_snapshots_every_charge_and_produces_the_exact_integer_total(): void
    {
        $reading = $this->recordedReading();

        $invoice = $this->calculator()->recalculateDraft($this->period, $this->room, $reading);
        $items = $invoice->items->keyBy(fn ($item): string => $item->type->value);

        $this->assertSame(InvoiceStatus::Draft, $invoice->status);
        $this->assertSame(4_187_400, $invoice->subtotal);
        $this->assertSame(4_187_400, $invoice->total);
        $this->assertIsInt($invoice->total);
        $this->assertCount(7, $items);

        $rent = $items->get(InvoiceItemType::Rent->value);
        $this->assertSame(1, $rent->quantity);
        $this->assertSame(3_200_000, $rent->unit_price);
        $this->assertSame(3_200_000, $rent->amount);

        $electricity = $items->get(InvoiceItemType::Electricity->value);
        $this->assertSame(217, $electricity->quantity);
        $this->assertSame(3_200, $electricity->unit_price);
        $this->assertSame(694_400, $electricity->amount);
        $this->assertSame(['previous' => 14_000, 'current' => 14_217], $electricity->metadata);

        $water = $items->get(InvoiceItemType::Water->value);
        $this->assertSame(4, $water->quantity);
        $this->assertSame(17_000, $water->unit_price);
        $this->assertSame(68_000, $water->amount);
        $this->assertSame(['previous' => 343, 'current' => 347], $water->metadata);

        $this->assertSame(120_000, $items->get(InvoiceItemType::Vehicle->value)->amount);
        $this->assertSame(30_000, $items->get(InvoiceItemType::Garbage->value)->amount);
        $this->assertSame(50_000, $items->get(InvoiceItemType::Cable->value)->amount);
        $this->assertSame(25_000, $items->get(InvoiceItemType::Other->value)->amount);
        $this->assertTrue($invoice->billingPeriod->is($this->period));
        $this->assertTrue($invoice->room->is($this->room));
    }

    public function test_saving_a_reading_automatically_creates_and_updates_one_draft_invoice(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('meter-readings.update', [$this->period, $this->floor, $this->room]), [
                'electricity_previous' => 14_000,
                'electricity_current' => 14_217,
                'water_previous' => 343,
                'water_current' => 347,
            ])
            ->assertRedirect();

        $invoice = Invoice::query()->sole();
        $this->assertSame(4_187_400, $invoice->total);

        $this->actingAs($admin)
            ->put(route('meter-readings.update', [$this->period, $this->floor, $this->room]), [
                'electricity_previous' => 14_000,
                'electricity_current' => 14_300,
                'water_previous' => 343,
                'water_current' => 350,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 7);
        $this->assertSame(4_504_000, $invoice->refresh()->total);
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'type' => InvoiceItemType::Electricity->value,
            'quantity' => 300,
            'unit_price' => 3_200,
            'amount' => 960_000,
        ]);
    }

    public function test_finalized_invoice_snapshot_does_not_change_after_prices_and_reading_change(): void
    {
        $reading = $this->recordedReading();
        $invoice = $this->calculator()->recalculateDraft($this->period, $this->room, $reading);
        $originalItems = $invoice->items
            ->map(fn ($item): array => $item->only([
                'type', 'quantity', 'unit_price', 'amount', 'metadata', 'sort_order',
            ]))
            ->all();

        $invoice->update([
            'status' => InvoiceStatus::Finalized,
            'locked_at' => now(),
        ]);
        $this->settings->update([
            'rent_amount' => 4_000_000,
            'electricity_unit_price' => 3_500,
            'water_unit_price' => 20_000,
            'vehicle_amount' => 200_000,
        ]);
        $reading->update([
            'electricity_current' => 14_500,
            'electricity_usage' => 500,
            'water_current' => 353,
            'water_usage' => 10,
        ]);

        $result = $this->calculator()->recalculateDraft(
            $this->period,
            $this->room->refresh(),
            $reading->refresh(),
        );

        $this->assertSame(InvoiceStatus::Finalized, $result->status);
        $this->assertSame(4_187_400, $result->total);
        $this->assertSame($originalItems, $result->items
            ->map(fn ($item): array => $item->only([
                'type', 'quantity', 'unit_price', 'amount', 'metadata', 'sort_order',
            ]))
            ->all());
    }

    public function test_vacant_room_has_a_zero_value_snapshot_without_charge_items(): void
    {
        $this->room->update(['status' => RoomStatus::Vacant]);

        $invoice = $this->calculator()->recalculateDraft(
            $this->period,
            $this->room->refresh(),
            $this->recordedReading(),
        );

        $this->assertSame(0, $invoice->subtotal);
        $this->assertSame(0, $invoice->total);
        $this->assertCount(0, $invoice->items);
    }

    public function test_invoice_pages_require_authentication_and_render_stored_snapshot_values(): void
    {
        $invoice = $this->calculator()->recalculateDraft(
            $this->period,
            $this->room,
            $this->recordedReading(),
        );

        $this->get(route('invoices.index', $this->period))->assertRedirect(route('login'));
        $this->get(route('invoices.show', [$this->period, $invoice]))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('invoices.show', [$this->period, $invoice]))
            ->assertOk()
            ->assertSeeText('Phòng 101')
            ->assertSeeText('Cũ 14.000')
            ->assertSeeText('Mới 14.217')
            ->assertSeeText('694.400 đ')
            ->assertSeeText('4.187.400 đ');
    }

    public function test_room_has_only_one_invoice_per_period(): void
    {
        Invoice::factory()->for($this->period)->for($this->room)->create();

        $this->expectException(QueryException::class);

        Invoice::factory()->for($this->period)->for($this->room)->create();
    }

    private function recordedReading(): MeterReading
    {
        return MeterReading::factory()
            ->for($this->period)
            ->for($this->room)
            ->recorded(
                electricityPrevious: 14_000,
                electricityCurrent: 14_217,
                waterPrevious: 343,
                waterCurrent: 347,
            )
            ->create();
    }

    private function calculator(): InvoiceCalculator
    {
        return $this->app->make(InvoiceCalculator::class);
    }
}
