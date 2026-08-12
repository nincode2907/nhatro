<?php

namespace App\Http\Controllers;

use App\Enums\RoomStatus;
use App\Models\Property;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(): View
    {
        $property = Property::query()
            ->where('code', config('property.code'))
            ->with([
                'floors' => fn ($floors) => $floors
                    ->orderBy('sort_order')
                    ->with([
                        'rooms' => fn ($rooms) => $rooms
                            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [RoomStatus::Occupied->value])
                            ->orderBy('sort_order')
                            ->orderBy('room_number'),
                    ]),
            ])
            ->first();

        return view('rooms.index', compact('property'));
    }
}
