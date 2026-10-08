<?php

namespace App\Support;

class Money
{
    public const DEFAULT_PRICES = [
        'rent_amount' => 3_000_000,
        'electricity_unit_price' => 3_200,
        'water_unit_price' => 17_000,
        'vehicle_amount' => 120_000,
        'garbage_amount' => 30_000,
        'cable_amount' => 0,
    ];

    public static function normalize(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return preg_match('/^[0-9]{1,3}(?:,[0-9]{3})+$/D', $value)
            ? str_replace(',', '', $value)
            : $value;
    }

    public static function format(mixed $value): string
    {
        $normalized = self::normalize($value);

        return is_scalar($normalized) && preg_match('/^[0-9]+$/D', (string) $normalized)
            ? preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', (string) $normalized)
            : (is_scalar($value) ? (string) $value : '');
    }
}
