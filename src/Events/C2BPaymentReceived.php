<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Starnerz\LaravelDaraja\Data\Callbacks\C2BTransaction;

/**
 * A customer paid your short code and Safaricom confirmed it.
 */
class C2BPaymentReceived
{
    use Dispatchable;

    public function __construct(public readonly C2BTransaction $transaction) {}
}
