<?php

namespace Tests\Feature;

use App\Enums\BillingPeriodStatus;
use App\Models\BillingPeriod;
use App\Models\Floor;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomSetting;
use App\Models\User;
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

    public function test_billing_period_pages_require_authentication(): void
    {
        $period = $this->period('2026-08');

        $this->get(route('billing-periods.index'))->assertRedirect(route('login'));
        $this->post(route('billing-periods.store'), ['period_key' => '2026-09'])
            ->assertRedirect(route('login'));
        $this->get(route('billing-periods.show', $period))->assertRedirect(route('login'));
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

    private function period(string $key): BillingPeriod
    {
        return BillingPeriod::factory()->for($this->property)->create([
            'period_key' => $key,
            'starts_on' => $key.'-01',
            'ends_on' => date('Y-m-t', strtotime($key.'-01')),
        ]);
    }
}
