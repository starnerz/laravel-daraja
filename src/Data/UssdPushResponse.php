<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Data;

/**
 * Acknowledgement returned by B2B Express Checkout (USSD Push to Till).
 *
 * This API does not follow the ResponseCode/ResponseDescription convention used
 * by the rest of Daraja — it answers with a lowercase "code" and "status".
 */
final readonly class UssdPushResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $code,
        public string $status,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: (string) ($data['code'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            raw: $data,
        );
    }

    public function initiated(): bool
    {
        return $this->code === '0';
    }
}
