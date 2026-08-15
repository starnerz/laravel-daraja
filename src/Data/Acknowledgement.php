<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Data;

/**
 * The common acknowledgement returned by the asynchronous Daraja APIs.
 *
 * These endpoints only confirm that Safaricom queued the request; the outcome
 * arrives separately on the result URL.
 */
final readonly class Acknowledgement
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $originatorConversationId,
        public string $conversationId,
        public string $responseCode,
        public string $responseDescription,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            // Safaricom spells this key both ways depending on the endpoint.
            originatorConversationId: (string) ($data['OriginatorConversationID']
                ?? $data['OriginatorCoversationID']
                ?? ''),
            conversationId: (string) ($data['ConversationID'] ?? ''),
            responseCode: (string) ($data['ResponseCode'] ?? ''),
            responseDescription: (string) ($data['ResponseDescription'] ?? ''),
            raw: $data,
        );
    }

    public function accepted(): bool
    {
        return $this->responseCode === '0';
    }
}
