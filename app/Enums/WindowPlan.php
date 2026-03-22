<?php

namespace App\Enums;

enum WindowPlan: string
{
    case Continuously = 'continuously';
    case Every6Months = 'every_6_months';
    case Every4Months = 'every_4_months';
    case Every3Months = 'every_3_months';
    case Every2Months = 'every_2_months';

    public function monthsInterval(): ?int
    {
        return match ($this) {
            self::Continuously => null,
            self::Every6Months => 6,
            self::Every4Months => 4,
            self::Every3Months => 3,
            self::Every2Months => 2,
        };
    }
}
