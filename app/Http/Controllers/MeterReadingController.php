<?php

namespace App\Http\Controllers;

use App\Actions\MeterReadings\EnsureMeterReading;
use App\Actions\MeterReadings\ReadingFlow;
use App\Actions\MeterReadings\SaveMeterReading;
use App\Actions\MeterReadings\SkipMeterReading;
use App\Enums\MeterReadingStatus;
use App\Enums\SkipReason;
use App\Http\Requests\MeterReadings\SkipMeterReadingRequest;
use App\Http\Requests\MeterReadings\StoreMeterReadingRequest;
use App\Models\BillingPeriod;
use App\Models\Floor;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MeterReadingController extends Controller
{
    public function floor(BillingPeriod $period, Floor $floor, ReadingFlow $readingFlow): RedirectResponse
    {
        $this->ensureRelated($period, $floor);
        $rooms = $readingFlow->roomsFor($floor);
        $room = $readingFlow->nextUnprocessed($period, $floor) ?? $rooms->first();

        if (! $room) {
            return redirect()
                ->route('billing-periods.show', $period)
                ->with('status', "{$floor->name} chưa có phòng đang sử dụng.");
        }

        return redirect()->route('meter-readings.show', [$period, $floor, $room]);
    }

    public function show(
        BillingPeriod $period,
        Floor $floor,
        Room $room,
        ReadingFlow $readingFlow,
        EnsureMeterReading $ensureMeterReading,
    ): View {
        $this->ensureRelated($period, $floor, $room);
        $room->loadMissing('settings');
        $reading = $period->isOpen()
            ? $ensureMeterReading->handle($period, $room)
            : $period->meterReadings()->whereBelongsTo($room)->firstOrNew([
                'room_id' => $room->id,
            ], [
                'status' => MeterReadingStatus::Pending,
                'meter_reset' => false,
            ]);
        $rooms = $readingFlow->roomsFor($floor);
        $statuses = $readingFlow->statusesFor($period, $rooms);
        $position = $rooms->search(fn (Room $candidate): bool => $candidate->is($room));
        abort_if($position === false, 404);

        return view('meter-readings.show', [
            'period' => $period,
            'floor' => $floor,
            'room' => $room,
            'rooms' => $rooms,
            'reading' => $reading,
            'statuses' => $statuses,
            'position' => $position + 1,
            'processed' => $readingFlow->processedCount($statuses),
            'skipReasons' => SkipReason::cases(),
        ]);
    }

    public function update(
        StoreMeterReadingRequest $request,
        BillingPeriod $period,
        Floor $floor,
        Room $room,
        SaveMeterReading $saveMeterReading,
    ): RedirectResponse {
        $this->ensureRelated($period, $floor, $room);
        $nextRoom = $saveMeterReading->handle($period, $floor, $room, $request->validated());

        return $this->nextRedirect($period, $floor, $room, $nextRoom, 'Đã lưu chỉ số');
    }

    public function skip(
        SkipMeterReadingRequest $request,
        BillingPeriod $period,
        Floor $floor,
        Room $room,
        SkipMeterReading $skipMeterReading,
    ): RedirectResponse {
        $this->ensureRelated($period, $floor, $room);
        $nextRoom = $skipMeterReading->handle($period, $floor, $room, $request->validated());

        return $this->nextRedirect($period, $floor, $room, $nextRoom, 'Đã bỏ qua');
    }

    private function nextRedirect(
        BillingPeriod $period,
        Floor $floor,
        Room $room,
        ?Room $nextRoom,
        string $action,
    ): RedirectResponse {
        if ($nextRoom) {
            return redirect()
                ->route('meter-readings.show', [$period, $floor, $nextRoom])
                ->with('status', "{$action} phòng {$room->room_number}. Tiếp theo: phòng {$nextRoom->room_number}.");
        }

        return redirect()
            ->route('billing-periods.show', $period)
            ->with('status', "{$action} phòng {$room->room_number}. {$floor->name} đã được xử lý xong.");
    }

    private function ensureRelated(BillingPeriod $period, Floor $floor, ?Room $room = null): void
    {
        $valid = $period->property()
            ->where('code', config('property.code'))
            ->where('is_active', true)
            ->exists()
            && $floor->property_id === $period->property_id
            && $floor->is_active;

        if ($room) {
            $valid = $valid
                && $room->floor_id === $floor->id
                && $room->is_active;
        }

        abort_unless($valid, 404);
    }
}
