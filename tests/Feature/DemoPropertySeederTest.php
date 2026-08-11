<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Room;
use App\Models\RoomSetting;
use Database\Seeders\DemoPropertySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoPropertySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_configured_property_four_floors_and_45_demo_rooms(): void
    {
        config()->set('property', [
            'code' => 'TEST-HOUSE',
            'name' => 'Nhà trọ kiểm thử',
            'address' => '123 Đường Mẫu',
        ]);

        $this->seed(DemoPropertySeeder::class);

        $property = Property::query()->where('code', 'TEST-HOUSE')->sole();

        $this->assertSame('Nhà trọ kiểm thử', $property->name);
        $this->assertSame(4, $property->floors()->count());
        $this->assertSame(45, Room::query()->count());
        $this->assertSame(45, RoomSetting::query()->count());

        $this->assertDatabaseHas('rooms', ['room_number' => '101', 'sort_order' => 1]);
        $this->assertDatabaseHas('rooms', ['room_number' => '412', 'sort_order' => 12]);
        $this->assertDatabaseHas('room_settings', [
            'rent_amount' => 3_000_000,
            'electricity_unit_price' => 3_200,
            'water_unit_price' => 17_000,
            'vehicle_amount' => 120_000,
            'garbage_amount' => 30_000,
        ]);
    }

    public function test_demo_seeder_is_idempotent_and_does_not_reset_edited_settings(): void
    {
        config()->set('property.code', 'TEST-HOUSE');
        config()->set('property.name', 'Nhà trọ kiểm thử');

        $this->seed(DemoPropertySeeder::class);

        $room = Room::query()->where('room_number', '101')->sole();
        $room->settings()->update(['rent_amount' => 4_200_000]);

        $this->seed(DemoPropertySeeder::class);

        $this->assertDatabaseCount('properties', 1);
        $this->assertDatabaseCount('floors', 4);
        $this->assertDatabaseCount('rooms', 45);
        $this->assertDatabaseCount('room_settings', 45);
        $this->assertSame(4_200_000, $room->settings()->value('rent_amount'));
    }
}
