<?php

namespace Tests\Feature;

use App\Enums\BillingPeriodStatus;
use App\Models\BillingPeriod;
use App\Models\Floor;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomSetting;
use App\Models\User;
use App\Services\Billing\InvoiceCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingPeriodManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('property.code', 'TEST-HOUSE');
        $this->admin = User::factory()->create();
        $this->property = Property::factory()->create(['code' => 'TEST-HOUSE']);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_billing_period_pages_require_authentication(): void
    {
        $period = $this->period('2026-08');

        $this->get(route('billing-periods.index'))->assertRedirect(route('login'));
        $this->post(route('billing-periods.store'), ['period_key' => '2026-09'])
            ->assertRedirect(route('login'));
        $this->get(route('billing-periods.show', $period))->assertRedirect(route('login'));
        $this->delete(route('billing-periods.readings.destroy', $period))->assertRedirect(route('login'));
    }

    public function test_admin_can_create_a_month_with_exact_boundaries_and_reopen_the_same_period(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('billing-periods.store'), ['period_key' => '2028-02']);

        $period = BillingPeriod::query()->sole();
        $response->assertRedirect(route('billing-periods.show', $period));
        $this->assertSame('2028-02-01', $period->starts_on->toDateString());
        $this->assertSame('2028-02-29', $period->ends_on->toDateString());
        $this->assertSame(BillingPeriodStatus::Open, $period->status);

        $this->actingAs($this->admin)
            ->post(route('billing-periods.store'), ['period_key' => '2028-02'])
            ->assertRedirect(route('billing-periods.show', $period));

        $this->assertDatabaseCount('billing_periods', 1);
    }

    public function test_invalid_month_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->from(route('billing-periods.index'))
            ->post(route('billing-periods.store'), ['period_key' => '2026-13'])
            ->assertRedirect(route('billing-periods.index'))
            ->assertSessionHasErrors('period_key');
    }

    public function test_period_page_shows_processed_progress_separately_for_each_floor(): void
    {
        $floor = Floor::factory()->for($this->property)->create(['name' => 'Tầng 1']);
        foreach (['101', '102', '103'] as $position => $number) {
            $room = Room::factory()->for($floor)->create([
                'room_number' => $number,
                'sort_order' => $position + 1,
            ]);
            RoomSetting::factory()->for($room)->create();
        }
        $period = $this->period('2026-08');

        $this->actingAs($this->admin)
            ->get(route('billing-periods.show', $period))
            ->assertOk()
            ->assertSeeText('Đã xử lý 0 / 3')
            ->assertSeeText('Bắt đầu ghi');
    }

    public function test_period_from_another_property_is_not_accessible(): void
    {
        $other = Property::factory()->create(['code' => 'OTHER-HOUSE']);
        $period = BillingPeriod::factory()->for($other)->create();

        $this->actingAs($this->admin)
            ->get(route('billing-periods.show', $period))
            ->assertNotFound();
    }

    public function test_reset_button_is_only_shown_for_the_open_current_month(): void
    {
        CarbonImmutable::setTestNow('2026-08-12 10:00:00');
        $current = $this->period('2026-08');
        $past = $this->period('2026-07');

        $this->actingAs($this->admin)
            ->get(route('billing-periods.show', $current))
            ->assertOk()
            ->assertSeeText('Xóa dữ liệu ghi thử')
            ->assertSee(route('billing-periods.readings.destroy', $current));

        $this->actingAs($this->admin)
            ->get(route('billing-periods.show', $past))
            ->assertOk()
            ->assertDontSeeText('Xóa dữ liệu ghi thử');

        $current->update(['status' => BillingPeriodStatus::Finalized]);

        $this->actingAs($this->admin)
            ->get(route('billing-periods.show', $current))
            ->assertOk()
            ->assertDontSeeText('Xóa dữ liệu ghi thử');
    }

    public function test_admin_can_delete_all_current_month_readings_and_draft_invoices(): void
    {
        CarbonImmutable::setTestNow('2026-08-12 10:00:00');
        $floor = Floor::factory()->for($this->property)->create();
        $room = Room::factory()->for($floor)->create();
        RoomSetting::factory()->for($room)->create();
        $past = $this->period('2026-07');
        $current = $this->period('2026-08');
        $pastReading = MeterReading::factory()->for($past)->for($room)->recorded()->create();
        $currentReading = MeterReading::factory()->for($current)->for($room)->recorded()->create();
        $invoice = $this->app->make(InvoiceCalculator::class)
            ->recalculateDraft($current, $room, $currentReading);

        $response = $this->actingAs($this->admin)
            ->delete(route('billing-periods.readings.destroy', $current));

        $response
            ->assertRedirect(route('billing-periods.show', $current))
            ->assertSessionHas('status', 'Đã xóa 1 bản ghi chỉ số và 1 hóa đơn nháp. Bạn có thể ghi lại từ đầu.');
        $this->assertDatabaseMissing('meter_readings', ['id' => $currentReading->id]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseMissing('invoice_items', ['invoice_id' => $invoice->id]);
        $this->assertDatabaseHas('meter_readings', ['id' => $pastReading->id]);
        $this->assertDatabaseHas('billing_periods', ['id' => $current->id]);
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
        $this->assertDatabaseHas('room_settings', ['room_id' => $room->id]);
        $this->assertSame(0, Invoice::query()->whereBelongsTo($current)->count());
        $this->assertSame(0, InvoiceItem::query()->where('invoice_id', $invoice->id)->count());
    }

    public function test_reset_endpoint_rejects_past_or_finalized_periods(): void
    {
        CarbonImmutable::setTestNow('2026-08-12 10:00:00');
        $floor = Floor::factory()->for($this->property)->create();
        $room = Room::factory()->for($floor)->create();
        $past = $this->period('2026-07');
        $current = $this->period('2026-08');
        $current->update(['status' => BillingPeriodStatus::Finalized]);
        $pastReading = MeterReading::factory()->for($past)->for($room)->recorded()->create();
        $finalizedReading = MeterReading::factory()->for($current)->for($room)->recorded()->create();

        $this->actingAs($this->admin)
            ->delete(route('billing-periods.readings.destroy', $past))
            ->assertForbidden();
        $this->actingAs($this->admin)
            ->delete(route('billing-periods.readings.destroy', $current))
            ->assertForbidden();

        $this->assertDatabaseHas('meter_readings', ['id' => $pastReading->id]);
        $this->assertDatabaseHas('meter_readings', ['id' => $finalizedReading->id]);
    }

    private function period(string $key): BillingPeriod
    {
        return BillingPeriod::factory()->for($this->property)->create([
            'period_key' => $key,
            'starts_on' => $key.'-01',
            'ends_on' => date('Y-m-t', strtotime($key.'-01')),
        ]);
    }
}
