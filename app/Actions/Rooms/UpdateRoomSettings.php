<?php

namespace App\Actions\Rooms;

use App\Enums\RoomStatus;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class UpdateRoomSettings
{
    public function __construct(private readonly ArrangeRoomWalkOrder $arrangeWalkOrder)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Room $room, array $data): Room
    {
        return DB::transaction(function () use ($room, $data): Room {
            $room = Room::query()->lockForUpdate()->findOrFail($room->id);
            $status = RoomStatus::from($data['status']);
            $sortOrder = $this->arrangeWalkOrder->handle(
                $room,
                $room->floor_id,
                $status,
                isset($data['sort_order']) ? (int) $data['sort_order'] : null,
            );

            $room->update([
                'sort_order' => $sortOrder,
                'status' => $status,
                'note' => $data['room_note'] ?? null,
                'is_active' => $status->isActive(),
            ]);

            $room->settings()->updateOrCreate([], [
                'rent_amount' => (int) $data['rent_amount'],
                'electricity_unit_price' => (int) $data['electricity_unit_price'],
                'water_unit_price' => (int) $data['water_unit_price'],
                'vehicle_amount' => (int) $data['vehicle_amount'],
                'garbage_amount' => (int) $data['garbage_amount'],
                'cable_amount' => (int) $data['cable_amount'],
                'other_amount' => (int) $data['other_amount'],
                'electricity_enabled' => (bool) $data['electricity_enabled'],
                'water_enabled' => (bool) $data['water_enabled'],
                'note' => $data['settings_note'] ?? null,
            ]);

            return $room->refresh()->load(['floor.property', 'settings']);
        });
    }
}
