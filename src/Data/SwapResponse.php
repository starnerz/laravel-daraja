<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Data;

final readonly class SwapResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $requestRefId,
        public string $responseCode,
        public string $responseDescription,
        public string $lastSwapDate,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            requestRefId: (string) ($data['requestRefID'] ?? ''),
            responseCode: (string) ($data['responseCode'] ?? ''),
            responseDescription: (string) ($data['responseDesc'] ?? ''),
            lastSwapDate: (string) ($data['lastSwapDate'] ?? ''),
            raw: $data,
        );
    }

    public function successful(): bool
    {
        return $this->responseCode === '200';
    }
}
