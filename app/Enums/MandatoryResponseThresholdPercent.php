<?php

namespace App\Enums;

enum MandatoryResponseThresholdPercent: int
{
    case Percent3 = 3;
    case Percent4 = 4;
    case Percent5 = 5;
    case Percent6 = 6;
}
