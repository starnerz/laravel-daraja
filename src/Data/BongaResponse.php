<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Data;

/**
 * Lipa na Bonga wraps every response in a header/body envelope and uses an
 * integer HTTP-style responseCode, unlike the rest of Daraja.
 */
final readonly class BongaResponse
{
    /**
     * @param  array<string, mixed>|null  $body
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $requestRefId,
        public int $responseCode,
        public string $responseMessage,
        public string $customerMessage,
        public string $timestamp,
        public ?array $body,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $header = is_array($data['header'] ?? null) ? $data['header'] : [];
        $body = is_array($data['body'] ?? null) ? $data['body'] : null;

        return new self(
            requestRefId: (string) ($header['requestRefId'] ?? ''),
            responseCode: (int) ($header['responseCode'] ?? 0),
            responseMessage: (string) ($header['responseMessage'] ?? ''),
            customerMessage: (string) ($header['customerMessage'] ?? ''),
            timestamp: (string) ($header['timestamp'] ?? ''),
            body: $body,
            raw: $data,
        );
    }

    public function successful(): bool
    {
        return $this->responseCode === 200 || $this->responseCode === 6000;
    }

    /**
     * Shilling value of the points, present on calculate-points responses.
     */
    public function amount(): ?string
    {
        return isset($this->body['amount']) ? (string) $this->body['amount'] : null;
    }

    public function points(): ?string
    {
        return isset($this->body['points']) ? (string) $this->body['points'] : null;
    }

    /**
     * Bonga-to-shilling conversion rate, currently 0.2.
     */
    public function rate(): ?string
    {
        return isset($this->body['rate']) ? (string) $this->body['rate'] : null;
    }
}
