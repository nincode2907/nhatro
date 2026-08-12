<?php

namespace App\Actions\Rooms;

use App\Enums\RoomStatus;
use App\Models\Room;

class ArrangeRoomWalkOrder
{
    public function handle(
        Room $room,
        int $targetFloorId,
        RoomStatus $targetStatus,
        ?int $requestedPosition,
    ): int {
        $floorIds = array_values(array_unique([$room->floor_id, $targetFloorId]));

        Room::query()
            ->whereIn('floor_id', $floorIds)
            ->lockForUpdate()
            ->get(['id']);

        if ($room->status->usesWalkOrder()) {
            Room::query()
                ->where('floor_id', $room->floor_id)
                ->where('status', RoomStatus::Occupied->value)
                ->where('sort_order', '>', $room->sort_order)
                ->whereKeyNot($room->id)
                ->decrement('sort_order');
        }

        if (! $targetStatus->usesWalkOrder()) {
            return 0;
        }

        $orderedRooms = Room::query()
            ->where('floor_id', $targetFloorId)
            ->where('status', RoomStatus::Occupied->value)
            ->whereKeyNot($room->id)
            ->count();
        $position = min(max(1, $requestedPosition ?? $orderedRooms + 1), $orderedRooms + 1);

        Room::query()
            ->where('floor_id', $targetFloorId)
            ->where('status', RoomStatus::Occupied->value)
            ->where('sort_order', '>=', $position)
            ->whereKeyNot($room->id)
            ->increment('sort_order');

        return $position;
    }
}
