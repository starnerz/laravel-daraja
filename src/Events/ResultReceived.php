<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Starnerz\LaravelDaraja\Data\Callbacks\ResultCallback;

/**
 * A result arrived for one of the asynchronous APIs.
 *
 * $type is the route segment the result came in on — "b2c", "b2b", "balance",
 * "reversal" or "transaction-status" — so a single listener can serve all of
 * them and branch as needed.
 */
class ResultReceived
{
    use Dispatchable;

    public function __construct(
        public readonly string $type,
        public readonly ResultCallback $result,
    ) {}
}
