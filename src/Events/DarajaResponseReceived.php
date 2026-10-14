<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Safaricom answered a Daraja request, successfully or not.
 *
 * Fires for every response the client receives, including 4xx and 5xx, and
 * before a failure becomes an ApiRequestException. A request that never got a
 * response at all — a connection failure — dispatches nothing, so a listener
 * recording both events can tell an unanswered request from a rejected one.
 */
class DarajaResponseReceived
{
    use Dispatchable;

    /**
     * @param  array<array-key, mixed>|string  $body  Redacted where the response
     *                                                decoded to an array, raw
     *                                                otherwise.
     * @param  string|null  $connection  Whose credentials the call was made
     *                                   with, when Daraja::using() named them
     */
    public function __construct(
        public readonly string $method,
        public readonly string $endpoint,
        public readonly int $status,
        public readonly array|string $body,
        public readonly float $durationMs,
        public readonly ?string $connection = null,
    ) {}

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }
}
