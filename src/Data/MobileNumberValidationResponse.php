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

    public function matches(): bool
    {
        if (in_array($this->responseCode, ['4000', '4001'], true)) {
            return $this->responseCode === '4000';
        }

        return in_array(strtolower($this->status), ['true', '1', 'match'], true);
    }
}
