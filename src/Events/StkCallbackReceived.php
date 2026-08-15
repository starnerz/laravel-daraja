<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Starnerz\LaravelDaraja\Data\Callbacks\StkCallback;

/**
 * An M-Pesa Express prompt reached its conclusion, successful or not.
 */
class StkCallbackReceived
{
    use Dispatchable;

    public function __construct(public readonly StkCallback $callback) {}
}
