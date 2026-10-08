<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'address', 'is_active', 'default_room_prices'])]
class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'default_room_prices' => 'array',
        ];
    }

    public function defaultRoomPrices(): array
    {
        return array_replace(Money::DEFAULT_PRICES, $this->default_room_prices ?? []);
    }

    /** @return HasMany<Floor, $this> */
    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class);
    }

    /** @return HasMany<BillingPeriod, $this> */
    public function billingPeriods(): HasMany
    {
        return $this->hasMany(BillingPeriod::class);
    }
}
