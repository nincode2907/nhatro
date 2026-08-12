<?php

namespace App\Actions\MeterReadings;

use App\Enums\MeterReadingStatus;
use App\Enums\RoomStatus;
use App\Models\BillingPeriod;
use App\Models\Floor;
use App\Models\MeterReading;
use App\Models\Room;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ReadingFlow
{
    /** @return EloquentCollection<int, Room> */
    public function roomsFor(Floor $floor): EloquentCollection
    {
        return $floor->rooms()
            ->where('is_active', true)
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [RoomStatus::Occupied->value])
            ->orderBy('sort_order')
            ->orderBy('room_number')
            ->get();
    }

    /** @param Collection<int, Room> $rooms
     * @return Collection<int, MeterReadingStatus>
     */
    public function statusesFor(BillingPeriod $period, Collection $rooms): Collection
    {
        $stored = MeterReading::query()
            ->whereBelongsTo($period)
            ->whereIn('room_id', $rooms->pluck('id'))
            ->pluck('status', 'room_id');

        return $rooms->mapWithKeys(function (Room $room) use ($stored): array {
            $value = $stored->get($room->id);

            return [$room->id => match (true) {
                $value instanceof MeterReadingStatus => $value,
                $value !== null => MeterReadingStatus::from($value),
                default => MeterReadingStatus::Pending,
            }];
        });
    }

    public function nextUnprocessed(BillingPeriod $period, Floor $floor, ?Room $after = null): ?Room
    {
        $rooms = $this->roomsFor($floor);
        $statuses = $this->statusesFor($period, $rooms);
        $pending = $rooms->filter(
            fn (Room $room): bool => $statuses->get($room->id) === MeterReadingStatus::Pending,
        )->values();

        if ($pending->isEmpty()) {
            return null;
        }

        if (! $after) {
            return $pending->first();
        }

        $afterIndex = $rooms->search(fn (Room $room): bool => $room->is($after));

        if ($afterIndex === false) {
            return $pending->first();
        }

        return $pending->first(
            fn (Room $room): bool => $rooms->search(fn (Room $candidate): bool => $candidate->is($room)) > $afterIndex,
        ) ?? $pending->first();
    }

    /** @param Collection<int, MeterReadingStatus> $statuses */
    public function processedCount(Collection $statuses): int
    {
        return $statuses->filter(fn (MeterReadingStatus $status): bool => $status->isProcessed())->count();
    }
}
