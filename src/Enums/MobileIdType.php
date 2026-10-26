<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Enums;

enum MobileIdType: string
{
    case NationalId = '01';
    case MilitaryId = '02';
    case Passport = '05';
}
