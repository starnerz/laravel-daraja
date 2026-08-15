<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Data;

/**
 * The acknowledgement returned when an STK push is accepted for processing.
 *
 * This is not the payment result — that arrives later on your callback URL.
 */
final readonly class StkPushResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $merchantRequestId,
        public string $checkoutRequestId,
        public string $responseCode,
        public string $responseDescription,
        public string $customerMessage,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            merchantRequestId: (string) ($data['MerchantRequestID'] ?? ''),
            checkoutRequestId: (string) ($data['CheckoutRequestID'] ?? ''),
            responseCode: (string) ($data['ResponseCode'] ?? ''),
            responseDescription: (string) ($data['ResponseDescription'] ?? ''),
            customerMessage: (string) ($data['CustomerMessage'] ?? ''),
            raw: $data,
        );
    }

    /**
     * Whether Safaricom accepted the request and pushed the prompt.
     */
    public function accepted(): bool
    {
        return $this->responseCode === '0';
    }
}
