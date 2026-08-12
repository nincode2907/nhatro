<?php

namespace App\Http\Controllers;

use App\Actions\Rooms\UpdateRoomSettings;
use App\Enums\RoomStatus;
use App\Http\Requests\Rooms\UpdateRoomSettingsRequest;
use App\Models\Room;
use App\Models\RoomSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoomSettingsController extends Controller
{
    public function edit(Room $room): View
    {
        $this->ensureRoomBelongsToConfiguredProperty($room);
        $room->loadMissing(['floor.property', 'settings']);

        $settings = $room->settings ?? new RoomSetting([
            'rent_amount' => 0,
            'electricity_unit_price' => 0,
            'water_unit_price' => 0,
            'vehicle_amount' => 0,
            'garbage_amount' => 0,
            'cable_amount' => 0,
            'other_amount' => 0,
            'electricity_enabled' => true,
            'water_enabled' => true,
        ]);

        return view('rooms.settings.edit', [
            'room' => $room,
            'settings' => $settings,
            'statuses' => RoomStatus::cases(),
            'nextWalkOrder' => max(1, (int) $room->floor->rooms()
                ->where('status', RoomStatus::Occupied->value)
                ->whereKeyNot($room->id)
                ->max('sort_order') + 1),
        ]);
    }

    public function update(
        UpdateRoomSettingsRequest $request,
        Room $room,
        UpdateRoomSettings $updateRoomSettings,
    ): RedirectResponse {
        $this->ensureRoomBelongsToConfiguredProperty($room);
        $updateRoomSettings->handle($room, $request->validated());

        return redirect()
            ->route('rooms.settings.edit', $room)
            ->with('status', 'Cấu hình phòng đã được lưu.');
    }

    private function ensureRoomBelongsToConfiguredProperty(Room $room): void
    {
        $belongsToProperty = $room->floor()
            ->whereHas('property', fn ($property) => $property->where('code', config('property.code')))
            ->exists();

        abort_unless($belongsToProperty, 404);
    }
}
