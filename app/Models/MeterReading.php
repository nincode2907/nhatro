<?php

namespace App\Models;

use App\Enums\MeterReadingStatus;
use App\Enums\SkipReason;
use Database\Factories\MeterReadingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'billing_period_id',
    'room_id',
    'status',
    'electricity_previous',
    'electricity_current',
    'electricity_usage',
    'water_previous',
    'water_current',
    'water_usage',
    'skip_reason',
    'note',
    'meter_reset',
    'recorded_at',
])]
class MeterReading extends Model
{
    /** @use HasFactory<MeterReadingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => MeterReadingStatus::class,
            'skip_reason' => SkipReason::class,
            'electricity_previous' => 'integer',
            'electricity_current' => 'integer',
            'electricity_usage' => 'integer',
            'water_previous' => 'integer',
            'water_current' => 'integer',
            'water_usage' => 'integer',
            'meter_reset' => 'boolean',
            'recorded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<BillingPeriod, $this> */
    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(BillingPeriod::class);
    }

    /** @return BelongsTo<Room, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
