<?php

namespace Tests\Feature;

use App\Enums\RoomStatus;
use App\Models\Floor;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomSetting;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertySchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_relationships_and_room_status_cast_are_configured(): void
    {
        $property = Property::factory()->create();
        $floor = Floor::factory()->for($property)->create();
        $room = Room::factory()->for($floor)->create(['status' => RoomStatus::Vacant]);
        $settings = RoomSetting::factory()->for($room)->create();

        $this->assertTrue($property->floors->contains($floor));
        $this->assertTrue($floor->rooms->contains($room));
        $this->assertTrue($room->floor->is($floor));
        $this->assertTrue($room->settings->is($settings));
        $this->assertTrue($settings->room->is($room));
        $this->assertSame(RoomStatus::Vacant, $room->status);
        $this->assertIsInt($settings->rent_amount);
    }

    public function test_property_code_must_be_unique(): void
    {
        Property::factory()->create(['code' => 'UNIQUE-CODE']);

        $this->expectException(QueryException::class);

        Property::factory()->create(['code' => 'UNIQUE-CODE']);
    }

    public function test_floor_code_must_be_unique_within_a_property(): void
    {
        $property = Property::factory()->create();
        Floor::factory()->for($property)->create(['code' => '1']);

        $this->expectException(QueryException::class);

        Floor::factory()->for($property)->create(['code' => '1']);
    }

    public function test_room_number_must_be_unique_within_a_floor(): void
    {
        $floor = Floor::factory()->create();
        Room::factory()->for($floor)->create(['room_number' => '101']);

        $this->expectException(QueryException::class);

        Room::factory()->for($floor)->create(['room_number' => '101']);
    }

    public function test_a_room_can_have_only_one_settings_record(): void
    {
        $room = Room::factory()->create();
        RoomSetting::factory()->for($room)->create();

        $this->expectException(QueryException::class);

        RoomSetting::factory()->for($room)->create();
    }

    public function test_parent_records_with_rooms_cannot_be_deleted(): void
    {
        $property = Property::factory()->create();
        $floor = Floor::factory()->for($property)->create();
        Room::factory()->for($floor)->create();

        $this->expectException(QueryException::class);

        $floor->delete();
    }
}
