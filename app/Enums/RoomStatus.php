<?php

namespace App\Enums;

enum RoomStatus: string
{
    case Occupied = 'OCCUPIED';
    case Vacant = 'VACANT';
    case Inactive = 'INACTIVE';

    public function label(): string
    {
        return match ($this) {
            self::Occupied => 'Đang thuê',
            self::Vacant => 'Phòng trống',
            self::Inactive => 'Ngừng sử dụng',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::Occupied => '●',
            self::Vacant => '○',
            self::Inactive => '—',
        };
    }

    public function isActive(): bool
    {
        return $this !== self::Inactive;
    }

    public function usesWalkOrder(): bool
    {
        return $this === self::Occupied;
    }
}
