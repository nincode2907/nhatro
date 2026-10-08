<?php

namespace App\Http\Controllers;

use App\Actions\Rooms\ArrangeRoomWalkOrder;
use App\Enums\RoomStatus;
use App\Http\Requests\PropertyStructure\StoreRoomRequest;
use App\Http\Requests\PropertyStructure\UpdateRoomStructureRequest;
use App\Http\Requests\PropertyStructure\UpsertFloorRequest;
use App\Models\Floor;
use App\Models\Property;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PropertyStructureController extends Controller
{
    public function index(): View
    {
        $property = $this->configuredProperty();
        $property->load([
            'floors' => fn ($floors) => $floors
                ->orderBy('sort_order')
                ->orderBy('code')
                ->with([
                    'rooms' => fn ($rooms) => $rooms
                        ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [RoomStatus::Occupied->value])
                        ->orderBy('sort_order')
                        ->orderBy('room_number'),
                ]),
        ]);

        return view('property-structure.index', compact('property'));
    }

    public function createFloor(): View
    {
        $property = $this->configuredProperty();
        $suggestedSortOrder = ((int) $property->floors()->max('sort_order')) + 1;

        return view('property-structure.floors.form', [
            'floor' => null,
            'suggestedSortOrder' => max(1, $suggestedSortOrder),
        ]);
    }

    public function storeFloor(UpsertFloorRequest $request): RedirectResponse
    {
        $property = $this->configuredProperty();
        $data = $request->validated();

        $floor = $property->floors()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'sort_order' => (int) $data['sort_order'],
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()
            ->route('property-structure.index')
            ->with('status', "Đã thêm {$floor->name}.");
    }

    public function editFloor(Floor $floor): View
    {
        $this->ensureFloor($floor);

        return view('property-structure.floors.form', [
            'floor' => $floor,
            'suggestedSortOrder' => $floor->sort_order,
        ]);
    }

    public function updateFloor(UpsertFloorRequest $request, Floor $floor): RedirectResponse
    {
        $this->ensureFloor($floor);
        $data = $request->validated();
        $floor->update([
            'code' => $data['code'],
            'name' => $data['name'],
            'sort_order' => (int) $data['sort_order'],
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()
            ->route('property-structure.index')
            ->with('status', "Đã cập nhật {$floor->name}.");
    }

    public function createRoom(Floor $floor): View
    {
        $this->ensureFloor($floor);
        $suggestedSortOrder = $this->nextWalkOrder($floor);

        return view('property-structure.rooms.form', [
            'room' => null,
            'floor' => $floor,
            'floors' => collect([$floor]),
            'statuses' => RoomStatus::cases(),
            'suggestedSortOrder' => max(1, $suggestedSortOrder),
        ]);
    }

    public function storeRoom(
        StoreRoomRequest $request,
        Floor $floor,
        ArrangeRoomWalkOrder $arrangeWalkOrder,
    ): RedirectResponse {
        $this->ensureFloor($floor);
        $data = $request->validated();
        $status = RoomStatus::from($data['status']);

        $room = DB::transaction(function () use ($floor, $data, $status, $arrangeWalkOrder): Room {
            $room = $floor->rooms()->create([
                'room_number' => $data['room_number'],
                'sort_order' => 0,
                'status' => RoomStatus::Vacant,
                'is_active' => true,
            ]);
            $sortOrder = $arrangeWalkOrder->handle(
                $room,
                $floor->id,
                $status,
                isset($data['sort_order']) ? (int) $data['sort_order'] : null,
            );
            $room->update([
                'sort_order' => $sortOrder,
                'status' => $status,
                'is_active' => $status->isActive(),
            ]);
            $room->settings()->create($floor->property->defaultRoomPrices());

            return $room;
        });

        return redirect()
            ->route('rooms.settings.edit', $room)
            ->with('status', "Đã thêm phòng {$room->room_number}. Đã áp dụng giá mặc định; bạn có thể sửa giá riêng của phòng.");
    }

    public function editRoom(Room $room): View
    {
        $property = $this->ensureRoom($room);
        $room->loadMissing('floor');

        return view('property-structure.rooms.form', [
            'room' => $room,
            'floor' => $room->floor,
            'floors' => $property->floors()->orderBy('sort_order')->orderBy('code')->get(),
            'statuses' => RoomStatus::cases(),
            'suggestedSortOrder' => $room->status->usesWalkOrder()
                ? $room->sort_order
                : $this->nextWalkOrder($room->floor, $room->id),
        ]);
    }

    public function updateRoom(
        UpdateRoomStructureRequest $request,
        Room $room,
        ArrangeRoomWalkOrder $arrangeWalkOrder,
    ): RedirectResponse {
        $property = $this->ensureRoom($room);
        $data = $request->validated();
        $targetFloor = $property->floors()->findOrFail($data['floor_id']);
        $status = RoomStatus::from($data['status']);

        $room = DB::transaction(function () use ($room, $targetFloor, $data, $status, $arrangeWalkOrder): Room {
            $room = Room::query()->lockForUpdate()->findOrFail($room->id);
            $sortOrder = $arrangeWalkOrder->handle(
                $room,
                $targetFloor->id,
                $status,
                isset($data['sort_order']) ? (int) $data['sort_order'] : null,
            );

            $room->update([
                'floor_id' => $targetFloor->id,
                'room_number' => $data['room_number'],
                'sort_order' => $sortOrder,
                'status' => $status,
                'is_active' => $status->isActive(),
            ]);

            return $room;
        });

        return redirect()
            ->route('property-structure.index')
            ->with('status', "Đã cập nhật phòng {$room->room_number}.");
    }

    public function destroyRoom(Room $room): RedirectResponse
    {
        $this->ensureRoom($room);
        $roomNumber = $room->room_number;
        $deleted = DB::transaction(function () use ($room): bool {
            $room = Room::query()->lockForUpdate()->findOrFail($room->id);

            if ($room->meterReadings()->exists() || $room->invoices()->exists()) {
                return false;
            }

            if ($room->status->usesWalkOrder()) {
                Room::query()
                    ->where('floor_id', $room->floor_id)
                    ->where('status', RoomStatus::Occupied->value)
                    ->where('sort_order', '>', $room->sort_order)
                    ->decrement('sort_order');
            }

            $room->settings()->delete();
            $room->delete();

            return true;
        });

        if (! $deleted) {
            return redirect()
                ->route('property-structure.rooms.edit', $room)
                ->withErrors([
                    'room' => 'Không thể xóa phòng đã có chỉ số hoặc hóa đơn. Hãy chuyển phòng sang “Ngừng sử dụng” để giữ lịch sử.',
                ]);
        }

        return redirect()
            ->route('property-structure.index')
            ->with('status', "Đã xóa phòng {$roomNumber} vì phòng chưa có lịch sử.");
    }

    private function configuredProperty(): Property
    {
        $property = Property::query()
            ->where('code', config('property.code'))
            ->where('is_active', true)
            ->first();

        abort_unless($property, 404);

        return $property;
    }

    private function ensureFloor(Floor $floor): Property
    {
        $property = $this->configuredProperty();
        abort_unless($floor->property_id === $property->id, 404);

        return $property;
    }

    private function ensureRoom(Room $room): Property
    {
        $property = $this->configuredProperty();
        $belongsToProperty = $room->floor()
            ->where('property_id', $property->id)
            ->exists();
        abort_unless($belongsToProperty, 404);

        return $property;
    }

    private function nextWalkOrder(Floor $floor, ?int $exceptRoomId = null): int
    {
        $rooms = $floor->rooms()
            ->where('status', RoomStatus::Occupied->value);

        if ($exceptRoomId !== null) {
            $rooms->whereKeyNot($exceptRoomId);
        }

        return max(1, ((int) $rooms->max('sort_order')) + 1);
    }
}
