<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * A Daraja request is about to leave the application.
 *
 * The payload carries the same redaction the logger applies, so a listener can
 * persist it without storing a SecurityCredential, a passkey or a consumer
 * secret.
 */
class DarajaRequestSending
{
    use Dispatchable;

    /**
     * @param  array<array-key, mixed>  $payload  Redacted
     */
    public function __construct(
        public readonly string $method,
        public readonly string $endpoint,
        public readonly array $payload,
    ) {}
}
