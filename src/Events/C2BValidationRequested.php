<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Starnerz\LaravelDaraja\Data\Callbacks\C2BTransaction;

/**
 * Safaricom is asking whether to accept a payment.
 *
 * This event is informational — it cannot decide the outcome, because Safaricom
 * needs an answer within about eight seconds. Register a decision callback with
 * Daraja::validateC2BUsing() to accept or reject.
 */
class C2BValidationRequested
{
    use Dispatchable;

    public function __construct(public readonly C2BTransaction $transaction) {}
}
