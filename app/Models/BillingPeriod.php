<?php

namespace App\Models;

use App\Enums\BillingPeriodStatus;
use Database\Factories\BillingPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'property_id',
    'period_key',
    'starts_on',
    'ends_on',
    'status',
    'finalized_by',
    'finalized_at',
])]
class BillingPeriod extends Model
{
    /** @use HasFactory<BillingPeriodFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'status' => BillingPeriodStatus::class,
            'finalized_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Property, $this> */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /** @return BelongsTo<User, $this> */
    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    /** @return HasMany<MeterReading, $this> */
    public function meterReadings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    public function label(): string
    {
        return 'Tháng '.$this->starts_on->format('m/Y');
    }

    public function isOpen(): bool
    {
        return $this->status === BillingPeriodStatus::Open;
    }
}
