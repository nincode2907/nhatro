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
}
