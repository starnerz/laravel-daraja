<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Data;

/**
 * M-Pesa Ratiba nests its response under header/body objects and uses
 * HTTP-style codes ("200"), not the "0" used elsewhere in Daraja.
 */
final readonly class StandingOrderResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $responseRefId,
        public string $responseCode,
        public string $responseDescription,
        public string $resultDescription,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Safaricom varies the casing of these keys between the synchronous
        // response and the callback, so both spellings are accepted.
        $header = $data['ResponseHeader'] ?? $data['responseHeader'] ?? [];
        $header = is_array($header) ? $header : [];

        return new self(
            responseRefId: (string) ($header['responseRefID'] ?? ''),
            responseCode: (string) ($header['responseCode'] ?? ''),
            responseDescription: (string) ($header['responseDescription'] ?? ''),
            resultDescription: (string) ($header['ResultDesc'] ?? ''),
            raw: $data,
        );
    }

    /**
     * The synchronous response uses 200; the callback uses 0.
     */
    public function accepted(): bool
    {
        return in_array($this->responseCode, ['200', '0'], true);
    }
}
