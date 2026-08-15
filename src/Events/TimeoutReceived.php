<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Safaricom could not process a request in time and hit the queue timeout URL.
 *
 * The payload shape is not consistently documented across APIs, so the raw
 * array is passed through untouched.
 */
class TimeoutReceived
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $type,
        public readonly array $payload,
    ) {}
}
