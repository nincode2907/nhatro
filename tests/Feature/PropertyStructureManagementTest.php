<?php

namespace Tests\Feature;

use App\Enums\RoomStatus;
use App\Models\BillingPeriod;
use App\Models\Floor;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyStructureManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Property $property;

    private Floor $floor;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('property.code', 'TEST-HOUSE');
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

    public function test_structure_management_requires_authentication(): void
    {
        $room = Room::factory()->for($this->floor)->create();

        $this->get(route('property-structure.index'))->assertRedirect(route('login'));
        $this->get(route('property-structure.floors.create'))->assertRedirect(route('login'));
        $this->post(route('property-structure.floors.store'), $this->floorPayload())
            ->assertRedirect(route('login'));
        $this->get(route('property-structure.rooms.edit', $room))->assertRedirect(route('login'));
        $this->delete(route('property-structure.rooms.destroy', $room))->assertRedirect(route('login'));
    }

    public function test_admin_can_open_structure_floor_and_room_forms(): void
    {
        $room = Room::factory()->for($this->floor)->create(['room_number' => '101']);

        $this->actingAs($this->admin)
            ->get(route('property-structure.index'))
            ->assertOk()
            ->assertSeeText('Tầng & phòng')
            ->assertSeeText('Phòng 101')
            ->assertSee('floor-accordion')
            ->assertSeeText('Quản lý phòng');
        $this->actingAs($this->admin)
            ->get(route('property-structure.floors.create'))
            ->assertOk()
            ->assertSeeText('Thêm tầng');
        $this->actingAs($this->admin)
            ->get(route('property-structure.rooms.create', $this->floor))
            ->assertOk()
            ->assertSeeText('Thêm phòng vào Tầng 1');
        $this->actingAs($this->admin)
            ->get(route('property-structure.rooms.edit', $room))
            ->assertOk()
            ->assertSeeText('Sửa phòng 101')
            ->assertSeeText('Xóa phòng 101')
            ->assertSee(route('property-structure.rooms.destroy', $room), false);
    }

    public function test_admin_can_add_and_edit_a_floor(): void
    {
        $this->actingAs($this->admin)
            ->post(route('property-structure.floors.store'), $this->floorPayload([
                'code' => '5',
                'name' => 'Tầng 5',
                'sort_order' => 5,
            ]))
            ->assertRedirect(route('property-structure.index'));

        $floor = Floor::query()->where('code', '5')->sole();
        $this->actingAs($this->admin)
            ->put(route('property-structure.floors.update', $floor), $this->floorPayload([
                'code' => 'S',
                'name' => 'Sân thượng',
                'sort_order' => 9,
                'is_active' => false,
            ]))
            ->assertRedirect(route('property-structure.index'));

        $this->assertDatabaseHas('floors', [
            'id' => $floor->id,
            'property_id' => $this->property->id,
            'code' => 'S',
            'name' => 'Sân thượng',
            'sort_order' => 9,
            'is_active' => false,
        ]);
    }

    public function test_floor_code_must_be_unique_inside_the_configured_property(): void
    {
        $this->actingAs($this->admin)
            ->from(route('property-structure.floors.create'))
            ->post(route('property-structure.floors.store'), $this->floorPayload([
                'code' => '1',
            ]))
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('floors', 1);
    }

    public function test_admin_can_add_a_room_with_zero_settings_then_configure_prices(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('property-structure.rooms.store', $this->floor), [
                'room_number' => '112',
                'sort_order' => 12,
                'status' => RoomStatus::Occupied->value,
            ]);

        $room = Room::query()->where('room_number', '112')->sole();
        $response
            ->assertRedirect(route('rooms.settings.edit', $room))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'floor_id' => $this->floor->id,
            'sort_order' => 1,
            'status' => RoomStatus::Occupied->value,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('room_settings', [
            'room_id' => $room->id,
            'rent_amount' => 0,
            'electricity_unit_price' => 0,
            'water_unit_price' => 0,
        ]);
    }

    public function test_admin_can_rename_move_and_deactivate_a_room_without_deleting_settings(): void
    {
        $secondFloor = Floor::factory()->for($this->property)->create([
            'code' => '2',
            'name' => 'Tầng 2',
            'sort_order' => 2,
        ]);
        $room = Room::factory()->for($this->floor)->create([
            'room_number' => '101',
            'sort_order' => 1,
        ]);
        RoomSetting::factory()->for($room)->create(['rent_amount' => 3_500_000]);

        $this->actingAs($this->admin)
            ->put(route('property-structure.rooms.update', $room), [
                'floor_id' => $secondFloor->id,
                'room_number' => '201A',
                'sort_order' => 7,
                'status' => RoomStatus::Inactive->value,
            ])
            ->assertRedirect(route('property-structure.index'));

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'floor_id' => $secondFloor->id,
            'room_number' => '201A',
            'sort_order' => 0,
            'status' => RoomStatus::Inactive->value,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('room_settings', [
            'room_id' => $room->id,
            'rent_amount' => 3_500_000,
        ]);
    }

    public function test_duplicate_room_number_is_rejected_only_within_the_target_floor(): void
    {
        $secondFloor = Floor::factory()->for($this->property)->create(['code' => '2']);
        Room::factory()->for($this->floor)->create(['room_number' => '101']);

        $this->actingAs($this->admin)
            ->post(route('property-structure.rooms.store', $this->floor), [
                'room_number' => '101',
                'sort_order' => 2,
                'status' => RoomStatus::Occupied->value,
            ])
            ->assertSessionHasErrors('room_number');

        $this->actingAs($this->admin)
            ->post(route('property-structure.rooms.store', $secondFloor), [
                'room_number' => '101',
                'sort_order' => 1,
                'status' => RoomStatus::Occupied->value,
            ])
            ->assertRedirect();

        $this->assertSame(2, Room::query()->where('room_number', '101')->count());
    }

    public function test_admin_can_delete_a_room_that_has_no_reading_or_invoice_history(): void
    {
        $room = Room::factory()->for($this->floor)->create(['room_number' => '109']);
        RoomSetting::factory()->for($room)->create();

        $this->actingAs($this->admin)
            ->delete(route('property-structure.rooms.destroy', $room))
            ->assertRedirect(route('property-structure.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
        $this->assertDatabaseMissing('room_settings', ['room_id' => $room->id]);
    }

    public function test_room_with_reading_or_invoice_history_cannot_be_deleted(): void
    {
        $period = BillingPeriod::factory()->for($this->property)->create();
        $roomWithReading = Room::factory()->for($this->floor)->create(['room_number' => '107']);
        $roomWithInvoice = Room::factory()->for($this->floor)->create(['room_number' => '108']);
        MeterReading::factory()->for($period)->for($roomWithReading)->create();
        Invoice::factory()->for($period)->for($roomWithInvoice)->create();

        foreach ([$roomWithReading, $roomWithInvoice] as $room) {
            $this->actingAs($this->admin)
                ->delete(route('property-structure.rooms.destroy', $room))
                ->assertRedirect(route('property-structure.rooms.edit', $room))
                ->assertSessionHasErrors('room');

            $this->assertDatabaseHas('rooms', ['id' => $room->id]);
        }
    }

    public function test_structure_from_another_property_is_not_accessible(): void
    {
        $otherProperty = Property::factory()->create(['code' => 'OTHER-HOUSE']);
        $otherFloor = Floor::factory()->for($otherProperty)->create();
        $otherRoom = Room::factory()->for($otherFloor)->create();

        $this->actingAs($this->admin)
            ->get(route('property-structure.floors.edit', $otherFloor))
            ->assertNotFound();
        $this->actingAs($this->admin)
            ->get(route('property-structure.rooms.edit', $otherRoom))
            ->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function floorPayload(array $overrides = []): array
    {
        return array_merge([
            'code' => '5',
            'name' => 'Tầng 5',
            'sort_order' => 5,
            'is_active' => true,
        ], $overrides);
    }
}
