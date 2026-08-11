<?php

namespace App\Enums;

enum SkipReason: string
{
    case NotRecordedToday = 'NOT_RECORDED_TODAY';
    case VacantRoom = 'VACANT_ROOM';
    case MeterNotAccessible = 'METER_NOT_ACCESSIBLE';
    case MeterProblem = 'METER_PROBLEM';
    case Other = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::NotRecordedToday => 'Chưa ghi được hôm nay',
            self::VacantRoom => 'Phòng đang trống',
            self::MeterNotAccessible => 'Không tiếp cận được đồng hồ',
            self::MeterProblem => 'Đồng hồ có vấn đề',
            self::Other => 'Lý do khác',
        };
    }
}
