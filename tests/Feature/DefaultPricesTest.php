<?php

namespace Tests\Feature;

use App\Enums\RoomStatus;
use App\Models\Floor;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultPricesTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_prices_require_login(): void
    {
        $this->get(route('default-prices.edit'))->assertRedirect(route('login'));
        $this->put(route('default-prices.update'), Money::DEFAULT_PRICES)->assertRedirect(route('login'));
    }

    public function test_saved_defaults_are_copied_to_new_rooms_and_can_be_overridden_individually(): void
    {
        config(['property.code' => 'TEST']);
        $property = Property::factory()->create(['code' => 'TEST']);
        $floor = Floor::factory()->for($property)->create();
        $admin = User::factory()->create();
        $prices = Money::DEFAULT_PRICES;
        $prices['rent_amount'] = 4_500_000;
        $this->actingAs($admin)->get(route('default-prices.edit'))->assertOk()->assertSee('3,000,000');
        $this->put(route('default-prices.update'), [...$prices, 'rent_amount' => '4,500,000'])->assertSessionHasNoErrors();
        $this->post(route('property-structure.rooms.store', $floor), [
            'room_number' => 'NEW', 'status' => RoomStatus::Occupied->value,
        ])->assertSessionHasNoErrors();
        $room = Room::where('room_number', 'NEW')->firstOrFail();
        $this->assertSame(4_500_000, $room->settings->rent_amount);
        $this->get(route('rooms.settings.edit', $room))->assertOk()->assertSee('4,500,000');
        $this->put(route('rooms.settings.update', $room), [
            ...$prices, 'rent_amount' => '5,000,000', 'other_amount' => '10,000',
            'status' => RoomStatus::Occupied->value, 'sort_order' => 1,
            'electricity_enabled' => 1, 'water_enabled' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertSame(5_000_000, $room->settings()->first()->rent_amount);
        $this->put(route('default-prices.update'), [...$prices, 'rent_amount' => '6,000,000'])->assertSessionHasNoErrors();
        $this->assertSame(5_000_000, $room->settings()->first()->rent_amount);
        $this->assertSame(6_000_000, $property->fresh()->defaultRoomPrices()['rent_amount']);
    }

    public function test_invalid_money_does_not_replace_defaults(): void
    {
        config(['property.code' => 'TEST']);
        $property = Property::factory()->create(['code' => 'TEST']);
        $this->actingAs(User::factory()->create());
        foreach (['1,2', '-1', '1.50', '1,000,000,001'] as $value) {
            $this->put(route('default-prices.update'), [...Money::DEFAULT_PRICES, 'rent_amount' => $value])
                ->assertSessionHasErrors('rent_amount');
        }
        $this->assertNull($property->fresh()->default_room_prices);
    }
}
