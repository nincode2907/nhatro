<?php

namespace Tests\Feature;

use App\Enums\BillingPeriodStatus;
use App\Enums\MeterReadingStatus;
use App\Enums\RoomStatus;
use App\Enums\SkipReason;
use App\Models\BillingPeriod;
use App\Models\Floor;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeterReadingFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Property $property;

    private Floor $floor;

    /** @var array<string, Room> */
    private array $rooms = [];

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('property.code', 'TEST-HOUSE');
        $this->admin = User::factory()->create();
        $this->property = Property::factory()->create(['code' => 'TEST-HOUSE']);
        $this->floor = Floor::factory()->for($this->property)->create([
            'code' => '1',
            'name' => 'Tầng 1',
        ]);

        foreach ([
            '101' => 1,
            '105' => 2,
            '103' => 3,
        ] as $number => $sortOrder) {
            $room = Room::factory()->for($this->floor)->create([
                'room_number' => $number,
                'sort_order' => $sortOrder,
            ]);
            RoomSetting::factory()->for($room)->create();
            $this->rooms[$number] = $room;
        }
    }

    public function test_reading_page_requires_authentication(): void
    {
        $period = $this->period('2026-08');

        $this->get($this->showRoute($period, $this->rooms['101']))
            ->assertRedirect(route('login'));
    }

    public function test_previous_values_are_auto_loaded_from_the_latest_valid_prior_reading(): void
    {
        $june = $this->period('2026-06');
        $july = $this->period('2026-07');
        $august = $this->period('2026-08');
        MeterReading::factory()->for($june)->for($this->rooms['101'])
            ->recorded(electricityPrevious: 800, electricityCurrent: 900, waterPrevious: 80, waterCurrent: 90)
            ->create();
        MeterReading::factory()->for($july)->for($this->rooms['101'])
            ->recorded(electricityPrevious: 900, electricityCurrent: 1200, waterPrevious: 90, waterCurrent: 96)
            ->create();

        $this->actingAs($this->admin)
            ->get($this->showRoute($august, $this->rooms['101']))
            ->assertOk()
            ->assertSeeText('PHÒNG 101 — 1/3')
            ->assertSeeText('Đã xử lý 0 / 3')
            ->assertSeeText('1.200')
            ->assertSeeText('96');

        $this->assertDatabaseHas('meter_readings', [
            'billing_period_id' => $august->id,
            'room_id' => $this->rooms['101']->id,
            'status' => MeterReadingStatus::Pending->value,
            'electricity_previous' => 1200,
            'water_previous' => 96,
        ]);
    }

    public function test_initial_baselines_are_required_only_when_no_prior_reading_exists(): void
    {
        $period = $this->period('2026-08');

        $this->actingAs($this->admin)
            ->from($this->showRoute($period, $this->rooms['101']))
            ->put($this->updateRoute($period, $this->rooms['101']), [
                'electricity_current' => 1200,
                'water_current' => 105,
            ])
            ->assertSessionHasErrors(['electricity_previous', 'water_previous']);

        $this->actingAs($this->admin)
            ->put($this->updateRoute($period, $this->rooms['101']), [
                'electricity_previous' => 1000,
                'electricity_current' => 1200,
                'water_previous' => 100,
                'water_current' => 105,
            ])
            ->assertRedirect($this->showRoute($period, $this->rooms['105']));

        $this->assertDatabaseHas('meter_readings', [
            'billing_period_id' => $period->id,
            'room_id' => $this->rooms['101']->id,
            'electricity_previous' => 1000,
            'electricity_current' => 1200,
            'electricity_usage' => 200,
            'water_previous' => 100,
            'water_current' => 105,
            'water_usage' => 5,
            'status' => MeterReadingStatus::Recorded->value,
        ]);
    }

    public function test_current_below_previous_is_rejected_without_a_meter_reset_exception(): void
    {
        $july = $this->period('2026-07');
        $august = $this->period('2026-08');
        MeterReading::factory()->for($july)->for($this->rooms['101'])->recorded()->create();
        $this->actingAs($this->admin)->get($this->showRoute($august, $this->rooms['101']));

        $this->actingAs($this->admin)
            ->from($this->showRoute($august, $this->rooms['101']))
            ->put($this->updateRoute($august, $this->rooms['101']), [
                'electricity_current' => 1099,
                'water_current' => 106,
                'meter_reset' => true,
            ])
            ->assertSessionHasErrors('electricity_current');

        $this->assertDatabaseHas('meter_readings', [
            'billing_period_id' => $august->id,
            'room_id' => $this->rooms['101']->id,
            'status' => MeterReadingStatus::Pending->value,
            'electricity_current' => null,
            'meter_reset' => false,
        ]);
    }

    public function test_disabled_meter_does_not_require_input(): void
    {
        $this->rooms['101']->settings()->update(['water_enabled' => false]);
        $period = $this->period('2026-08');

        $this->actingAs($this->admin)
            ->put($this->updateRoute($period, $this->rooms['101']), [
                'electricity_previous' => 1000,
                'electricity_current' => 1015,
            ])
            ->assertRedirect($this->showRoute($period, $this->rooms['105']));

        $this->assertDatabaseHas('meter_readings', [
            'billing_period_id' => $period->id,
            'room_id' => $this->rooms['101']->id,
            'status' => MeterReadingStatus::Recorded->value,
            'electricity_usage' => 15,
            'water_previous' => null,
            'water_current' => null,
            'water_usage' => null,
        ]);
    }

    public function test_ok_and_next_uses_walk_order_and_wraps_to_an_earlier_pending_room(): void
    {
        $period = $this->period('2026-08');

        $this->actingAs($this->admin)
            ->put($this->updateRoute($period, $this->rooms['103']), $this->baselinePayload())
            ->assertRedirect($this->showRoute($period, $this->rooms['101']));

        $this->actingAs($this->admin)
            ->put($this->updateRoute($period, $this->rooms['101']), $this->baselinePayload())
            ->assertRedirect($this->showRoute($period, $this->rooms['105']));
    }

    public function test_room_list_shows_every_active_room_with_reading_and_vacancy_states_and_direct_links(): void
    {
        $period = $this->period('2026-08');
        $this->rooms['105']->update(['status' => RoomStatus::Vacant]);
        MeterReading::factory()->for($period)->for($this->rooms['101'])->recorded()->create();
        MeterReading::factory()->for($period)->for($this->rooms['105'])->create([
            'status' => MeterReadingStatus::Skipped,
            'skip_reason' => SkipReason::VacantRoom,
        ]);

        $response = $this->actingAs($this->admin)
            ->get($this->showRoute($period, $this->rooms['103']))
            ->assertOk()
            ->assertSeeText('Đã xử lý 2 / 3')
            ->assertSeeText('Đã ghi')
            ->assertSeeText('Đã bỏ qua')
            ->assertSeeText('Phòng trống')
            ->assertSeeText('Chưa ghi');

        foreach ($this->rooms as $room) {
            $response->assertSee($this->showRoute($period, $room), false);
        }
    }

    public function test_skip_requires_a_reason_and_saves_reason_and_note(): void
    {
        $period = $this->period('2026-08');

        $this->actingAs($this->admin)
            ->from($this->showRoute($period, $this->rooms['101']))
            ->post($this->skipRoute($period, $this->rooms['101']), [])
            ->assertSessionHasErrors('skip_reason', null, 'skip');

        $this->actingAs($this->admin)
            ->post($this->skipRoute($period, $this->rooms['101']), [
                'skip_reason' => SkipReason::MeterNotAccessible->value,
                'note' => 'Khóa cửa khu đồng hồ',
            ])
            ->assertRedirect($this->showRoute($period, $this->rooms['105']));

        $this->assertDatabaseHas('meter_readings', [
            'billing_period_id' => $period->id,
            'room_id' => $this->rooms['101']->id,
            'status' => MeterReadingStatus::Skipped->value,
            'skip_reason' => SkipReason::MeterNotAccessible->value,
            'note' => 'Khóa cửa khu đồng hồ',
        ]);
    }

    public function test_vacant_skip_reason_does_not_silently_change_room_status(): void
    {
        $period = $this->period('2026-08');

        $this->actingAs($this->admin)
            ->post($this->skipRoute($period, $this->rooms['101']), [
                'skip_reason' => SkipReason::VacantRoom->value,
            ])
            ->assertRedirect();

        $this->assertSame(RoomStatus::Occupied, $this->rooms['101']->refresh()->status);
        $this->assertDatabaseHas('meter_readings', [
            'room_id' => $this->rooms['101']->id,
            'status' => MeterReadingStatus::Skipped->value,
            'skip_reason' => SkipReason::VacantRoom->value,
        ]);
    }

    public function test_finalized_period_rejects_recording_and_skip_changes(): void
    {
        $period = $this->period('2026-08');
        $period->update(['status' => BillingPeriodStatus::Finalized]);

        $this->actingAs($this->admin)
            ->from($this->showRoute($period, $this->rooms['101']))
            ->put($this->updateRoute($period, $this->rooms['101']), $this->baselinePayload())
            ->assertSessionHasErrors('period');

        $this->actingAs($this->admin)
            ->from($this->showRoute($period, $this->rooms['101']))
            ->post($this->skipRoute($period, $this->rooms['101']), [
                'skip_reason' => SkipReason::NotRecordedToday->value,
            ])
            ->assertSessionHasErrors('period');

        $this->assertDatabaseMissing('meter_readings', [
            'billing_period_id' => $period->id,
            'room_id' => $this->rooms['101']->id,
            'status' => MeterReadingStatus::Recorded->value,
        ]);
    }

    /** @return array<string, int> */
    private function baselinePayload(): array
    {
        return [
            'electricity_previous' => 1000,
            'electricity_current' => 1100,
            'water_previous' => 100,
            'water_current' => 105,
        ];
    }

    private function period(string $key): BillingPeriod
    {
        return BillingPeriod::factory()->for($this->property)->create([
            'period_key' => $key,
            'starts_on' => $key.'-01',
            'ends_on' => date('Y-m-t', strtotime($key.'-01')),
        ]);
    }

    private function showRoute(BillingPeriod $period, Room $room): string
    {
        return route('meter-readings.show', [$period, $this->floor, $room]);
    }

    private function updateRoute(BillingPeriod $period, Room $room): string
    {
        return route('meter-readings.update', [$period, $this->floor, $room]);
    }

    private function skipRoute(BillingPeriod $period, Room $room): string
    {
        return route('meter-readings.skip', [$period, $this->floor, $room]);
    }
}
