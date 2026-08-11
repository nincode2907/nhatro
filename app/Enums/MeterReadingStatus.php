<?php

namespace App\Enums;

enum MeterReadingStatus: string
{
    case Pending = 'PENDING';
    case Recorded = 'RECORDED';
    case Skipped = 'SKIPPED';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chưa ghi',
            self::Recorded => 'Đã ghi',
            self::Skipped => 'Đã bỏ qua',
        };
    }

    public function isProcessed(): bool
    {
        return $this !== self::Pending;
    }
}
