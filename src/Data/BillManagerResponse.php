<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Data;

/**
 * Bill Manager uses its own response envelope: lowercase "rescode"/"resmsg"
 * with HTTP-style codes, rather than Daraja's ResponseCode/ResponseDescription.
 */
final readonly class BillManagerResponse
{
    /**
     * @param  list<mixed>  $errors
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $code,
        public string $message,
        public ?string $statusMessage,
        public ?string $appKey,
        public array $errors,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: (string) ($data['rescode'] ?? ''),
            message: (string) ($data['resmsg'] ?? ''),
            statusMessage: isset($data['Status_Message']) ? (string) $data['Status_Message'] : null,
            // Returned once, on opt-in. Store it — bulk invoicing requires it.
            appKey: isset($data['app_key']) ? (string) $data['app_key'] : null,
            errors: is_array($data['errors'] ?? null) ? array_values($data['errors']) : [],
            raw: $data,
        );
    }

    public function successful(): bool
    {
        return $this->code === '200';
    }
}
