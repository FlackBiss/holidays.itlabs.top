<?php

namespace App\Enum;

enum GeoSource: string
{
    case MANUAL = 'manual';
    case CALIBRATED = 'calibrated';
}
