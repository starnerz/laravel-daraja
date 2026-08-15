<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Enums;

/**
 * How often an M-Pesa Ratiba standing order executes.
 */
enum StandingOrderFrequency: string
{
    case OneOff = '1';
    case Daily = '2';
    case Weekly = '3';
    case BiWeekly = '4';
    case Monthly = '5';
    case BiMonthly = '6';
    case Quarterly = '7';
    case HalfYearly = '8';
    case Yearly = '9';
}
