<?php

namespace App\Enums;

enum InvoiceItemType: string
{
    case Rent = 'RENT';
    case Electricity = 'ELECTRICITY';
    case Water = 'WATER';
    case Vehicle = 'VEHICLE';
    case Garbage = 'GARBAGE';
    case Cable = 'CABLE';
    case Other = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::Rent => 'Tiền phòng',
            self::Electricity => 'Điện',
            self::Water => 'Nước',
            self::Vehicle => 'Xe',
            self::Garbage => 'Rác',
            self::Cable => 'Cáp / Internet',
            self::Other => 'Khoản khác',
        };
    }

    public function isMetered(): bool
    {
        return in_array($this, [self::Electricity, self::Water], true);
    }
}
