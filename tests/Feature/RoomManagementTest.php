<?php

namespace Tests\Feature;

use App\Enums\RoomStatus;
use App\Models\Floor;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Property $property;

    private Floor $floor;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('property.code', 'TEST-HOUSE');
        config()->set('property.name', 'Nhà trọ kiểm thử');

        $this->admin = User::factory()->create();
        $this->property = Property::factory()->create([
            'code' => 'TEST-HOUSE',
            'name' => 'Nhà trọ kiểm thử',
        ]);
        $this->floor = Floor::factory()->for($this->property)->create([
            'code' => '1',
            'name' => 'Tầng 1',
            'sort_order' => 1,
        ]);
    }

    public function test_room_pages_require_authentication(): void
    {
        $room = $this->createRoomWithSettings('101');

        $this->get(route('rooms.index'))->assertRedirect(route('login'));
        $this->get(route('rooms.settings.edit', $room))->assertRedirect(route('login'));
        $this->put(route('rooms.settings.update', $room), $this->validPayload())
            ->assertRedirect(route('login'));
    }

    public function test_room_list_is_grouped_by_floor_and_sorted_by_walk_order(): void
    {
        $this->createRoomWithSettings('101', sortOrder: 2);
        $this->createRoomWithSettings('105', sortOrder: 1, status: RoomStatus::Vacant);

        $this->actingAs($this->admin)
            ->get(route('rooms.index'))
            ->assertOk()
            ->assertSee('Nhà trọ kiểm thử')
            ->assertSee('Tầng 1')
            ->assertSeeInOrder(['Phòng 101', 'Phòng 105'])
            ->assertSee('Phòng trống')
            ->assertSee('floor-accordion')
            ->assertSeeText('Xem phòng');
    }

    public function test_room_settings_page_displays_current_configuration(): void
    {
        $room = $this->createRoomWithSettings('101');

        $this->actingAs($this->admin)
            ->get(route('rooms.settings.edit', $room))
            ->assertOk()
            ->assertSee('Phòng 101')
            ->assertSee('Tiền phòng')
            ->assertSee('3.000.000 đ')
            ->assertSee('Có đồng hồ điện');
    }

    public function test_admin_can_update_room_status_order_prices_and_meter_flags(): void
    {
        $room = $this->createRoomWithSettings('101');

        $this->actingAs($this->admin)
            ->put(route('rooms.settings.update', $room), $this->validPayload([
                'status' => RoomStatus::Vacant->value,
                'sort_order' => 7,
                'room_note' => 'Phòng đang sửa',
                'rent_amount' => 3_500_000,
                'electricity_unit_price' => 3_500,
                'water_unit_price' => 18_000,
                'vehicle_amount' => 150_000,
                'garbage_amount' => 40_000,
                'cable_amount' => 80_000,
                'other_amount' => 25_000,
                'electricity_enabled' => false,
                'water_enabled' => true,
                'settings_note' => 'Giá demo đã được thay',
            ]))
            ->assertRedirect(route('rooms.settings.edit', $room))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => RoomStatus::Vacant->value,
            'sort_order' => 0,
            'note' => 'Phòng đang sửa',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('room_settings', [
            'room_id' => $room->id,
            'rent_amount' => 3_500_000,
            'electricity_unit_price' => 3_500,
            'water_unit_price' => 18_000,
            'vehicle_amount' => 150_000,
            'garbage_amount' => 40_000,
            'cable_amount' => 80_000,
            'other_amount' => 25_000,
            'electricity_enabled' => false,
            'water_enabled' => true,
            'note' => 'Giá demo đã được thay',
        ]);
    }

    public function test_inactive_status_deactivates_the_room_without_deleting_it(): void
    {
        $room = $this->createRoomWithSettings('101');

        $this->actingAs($this->admin)
            ->put(route('rooms.settings.update', $room), $this->validPayload([
                'status' => RoomStatus::Inactive->value,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => RoomStatus::Inactive->value,
            'is_active' => false,
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('room_settings', ['room_id' => $room->id]);
    }

    public function test_invalid_status_and_negative_money_are_rejected(): void
    {
        $room = $this->createRoomWithSettings('101');

        $this->actingAs($this->admin)
            ->from(route('rooms.settings.edit', $room))
            ->put(route('rooms.settings.update', $room), $this->validPayload([
                'status' => 'UNKNOWN',
                'rent_amount' => -1,
            ]))
            ->assertRedirect(route('rooms.settings.edit', $room))
            ->assertSessionHasErrors(['status', 'rent_amount']);

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => RoomStatus::Occupied->value,
        ]);
        $this->assertDatabaseHas('room_settings', [
            'room_id' => $room->id,
            'rent_amount' => 3_000_000,
        ]);
    }

    public function test_update_creates_settings_if_the_room_does_not_have_one(): void
    {
        $room = Room::factory()->for($this->floor)->create(['room_number' => '101']);

        $this->actingAs($this->admin)
            ->put(route('rooms.settings.update', $room), $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('room_settings', [
            'room_id' => $room->id,
            'rent_amount' => 3_000_000,
        ]);
    }

    public function test_moving_a_room_to_an_earlier_walk_position_shifts_the_rooms_in_between(): void
    {
        $first = $this->createRoomWithSettings('101', 1);
        $second = $this->createRoomWithSettings('102', 2);
        $third = $this->createRoomWithSettings('103', 3);
        $fourth = $this->createRoomWithSettings('104', 4);
        $fifth = $this->createRoomWithSettings('105', 5);

        $this->actingAs($this->admin)
            ->put(route('rooms.settings.update', $fifth), $this->validPayload(['sort_order' => 2]))
            ->assertRedirect();

        $this->assertSame(1, $first->refresh()->sort_order);
        $this->assertSame(2, $fifth->refresh()->sort_order);
        $this->assertSame(3, $second->refresh()->sort_order);
        $this->assertSame(4, $third->refresh()->sort_order);
        $this->assertSame(5, $fourth->refresh()->sort_order);
    }

    public function test_vacant_room_has_no_walk_order_and_rejoining_appends_to_the_active_order(): void
    {
        $first = $this->createRoomWithSettings('101', 1);
        $second = $this->createRoomWithSettings('102', 2);
        $third = $this->createRoomWithSettings('103', 3);
        $vacantPayload = $this->validPayload(['status' => RoomStatus::Vacant->value]);
        unset($vacantPayload['sort_order']);

        $this->actingAs($this->admin)
            ->put(route('rooms.settings.update', $second), $vacantPayload)
            ->assertRedirect();

        $this->assertSame(1, $first->refresh()->sort_order);
        $this->assertSame(0, $second->refresh()->sort_order);
        $this->assertSame(2, $third->refresh()->sort_order);
        $this->actingAs($this->admin)
            ->get(route('rooms.settings.edit', $second))
            ->assertOk()
            ->assertSeeText('Phòng này không tham gia thứ tự đi thực tế.');

        $rejoinPayload = $this->validPayload();
        unset($rejoinPayload['sort_order']);
        $this->actingAs($this->admin)
            ->put(route('rooms.settings.update', $second), $rejoinPayload)
            ->assertRedirect();

        $this->assertSame(3, $second->refresh()->sort_order);
    }

    public function test_room_from_another_property_is_not_accessible(): void
    {
        $otherProperty = Property::factory()->create(['code' => 'OTHER-HOUSE']);
        $otherFloor = Floor::factory()->for($otherProperty)->create();
        $otherRoom = Room::factory()->for($otherFloor)->create();

        $this->actingAs($this->admin)
            ->get(route('rooms.settings.edit', $otherRoom))
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->put(route('rooms.settings.update', $otherRoom), $this->validPayload())
            ->assertNotFound();
    }

    private function createRoomWithSettings(
        string $roomNumber,
        int $sortOrder = 1,
        RoomStatus $status = RoomStatus::Occupied,
    ): Room {
        $room = Room::factory()->for($this->floor)->create([
            'room_number' => $roomNumber,
            'sort_order' => $sortOrder,
            'status' => $status,
        ]);
        RoomSetting::factory()->for($room)->create();

        return $room;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'status' => RoomStatus::Occupied->value,
            'sort_order' => 1,
            'room_note' => null,
            'rent_amount' => 3_000_000,
            'electricity_unit_price' => 3_200,
            'water_unit_price' => 17_000,
            'vehicle_amount' => 120_000,
            'garbage_amount' => 30_000,
            'cable_amount' => 0,
            'other_amount' => 0,
            'electricity_enabled' => true,
            'water_enabled' => true,
            'settings_note' => null,
        ], $overrides);
    }
}
