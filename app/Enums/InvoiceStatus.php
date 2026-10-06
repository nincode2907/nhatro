<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'DRAFT';
    case Finalized = 'FINALIZED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Đã tạo',
            self::Finalized => 'Đã chốt',
        };
    }
}
