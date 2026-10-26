<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Data;

final readonly class MobileNumberValidationResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $responseRefId,
        public string $responseCode,
        public string $responseMessage,
        public string $status,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            responseRefId: (string) ($data['responseRefID'] ?? ''),
            responseCode: (string) ($data['responseCode'] ?? ''),
            responseMessage: (string) ($data['responseMessage'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            raw: $data,
        );
    }

    /**
     * 4000 is a match and 4001 a mismatch; `status` repeats it as a string.
     */
    public function matches(): bool
    {
        return $this->responseCode === '4000';
    }
}
