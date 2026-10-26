<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Data;

final readonly class B2CHakikishaResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $requestId,
        public string $timestamp,
        public string $status,
        public string $message,
        public string $firstName,
        public string $middleName,
        public string $lastName,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $header = is_array($data['header'] ?? null) ? $data['header'] : [];
        $body = is_array($data['body'] ?? null) ? $data['body'] : [];

        return new self(
            requestId: (string) ($header['requestID'] ?? ''),
            timestamp: (string) ($header['timestamp'] ?? ''),
            status: (string) ($header['status'] ?? ''),
            message: (string) ($header['message'] ?? ''),
            firstName: (string) ($body['firstName'] ?? ''),
            middleName: (string) ($body['middleName'] ?? ''),
            lastName: (string) ($body['lastName'] ?? ''),
            raw: $data,
        );
    }

    public function successful(): bool
    {
        return $this->status === '200';
    }
}
