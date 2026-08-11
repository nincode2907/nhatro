<?php

namespace App\Models;

use Database\Factories\RoomSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'room_id',
    'rent_amount',
    'electricity_unit_price',
    'water_unit_price',
    'vehicle_amount',
    'garbage_amount',
    'cable_amount',
    'other_amount',
    'electricity_enabled',
    'water_enabled',
    'note',
])]
class RoomSetting extends Model
{
    /** @use HasFactory<RoomSettingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'rent_amount' => 'integer',
            'electricity_unit_price' => 'integer',
            'water_unit_price' => 'integer',
            'vehicle_amount' => 'integer',
            'garbage_amount' => 'integer',
            'cable_amount' => 'integer',
            'other_amount' => 'integer',
            'electricity_enabled' => 'boolean',
            'water_enabled' => 'boolean',
        ];
    }

    /** @return BelongsTo<Room, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
