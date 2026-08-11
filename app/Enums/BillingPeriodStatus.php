<?php

namespace App\Enums;

enum BillingPeriodStatus: string
{
    case Open = 'OPEN';
    case Finalized = 'FINALIZED';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Đang mở',
            self::Finalized => 'Đã chốt',
        };
    }
}
