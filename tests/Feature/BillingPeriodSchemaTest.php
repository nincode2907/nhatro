<?php

namespace Tests\Feature;

use App\Enums\BillingPeriodStatus;
use App\Enums\MeterReadingStatus;
use App\Models\BillingPeriod;
use App\Models\Floor;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingPeriodSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_period_and_meter_reading_relationships_and_casts_are_configured(): void
    {
        $property = Property::factory()->create();
        $floor = Floor::factory()->for($property)->create();
        $room = Room::factory()->for($floor)->create();
        $period = BillingPeriod::factory()->for($property)->create([
            'period_key' => '2026-08',
            'starts_on' => '2026-08-01',
            'ends_on' => '2026-08-31',
        ]);
        $reading = MeterReading::factory()->for($period)->for($room)->create();

        $this->assertTrue($property->billingPeriods->contains($period));
        $this->assertTrue($period->property->is($property));
        $this->assertTrue($period->meterReadings->contains($reading));
        $this->assertTrue($room->meterReadings->contains($reading));
        $this->assertTrue($reading->billingPeriod->is($period));
        $this->assertTrue($reading->room->is($room));
        $this->assertSame(BillingPeriodStatus::Open, $period->status);
        $this->assertSame(MeterReadingStatus::Pending, $reading->status);
    }

    public function test_period_key_is_unique_within_a_property(): void
    {
        $property = Property::factory()->create();
        BillingPeriod::factory()->for($property)->create(['period_key' => '2026-08']);

        $this->expectException(QueryException::class);

        BillingPeriod::factory()->for($property)->create(['period_key' => '2026-08']);
    }

    public function test_room_has_only_one_reading_per_period(): void
    {
        $property = Property::factory()->create();
        $floor = Floor::factory()->for($property)->create();
        $room = Room::factory()->for($floor)->create();
        $period = BillingPeriod::factory()->for($property)->create();
        MeterReading::factory()->for($period)->for($room)->create();

        $this->expectException(QueryException::class);

        MeterReading::factory()->for($period)->for($room)->create();
    }

    public function test_room_and_period_history_cannot_be_deleted_while_a_reading_exists(): void
    {
        $property = Property::factory()->create();
        $floor = Floor::factory()->for($property)->create();
        $room = Room::factory()->for($floor)->create();
        $period = BillingPeriod::factory()->for($property)->create();
        MeterReading::factory()->for($period)->for($room)->create();

        try {
            $room->delete();
            $this->fail('The room delete should have been restricted.');
        } catch (QueryException) {
            $this->assertDatabaseHas('rooms', ['id' => $room->id]);
        }

        $this->expectException(QueryException::class);
        $period->delete();
    }

    public function test_finalizing_user_cannot_be_deleted_while_referenced(): void
    {
        $user = User::factory()->create();
        BillingPeriod::factory()->create([
            'status' => BillingPeriodStatus::Finalized,
            'finalized_by' => $user->id,
            'finalized_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        $user->delete();
    }
}
