<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Data;

/**
 * The result of querying an STK push that was previously initiated.
 */
final readonly class StkQueryResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $merchantRequestId,
        public string $checkoutRequestId,
        public string $responseCode,
        public string $responseDescription,
        public string $resultCode,
        public string $resultDescription,
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
            resultCode: (string) ($data['ResultCode'] ?? ''),
            resultDescription: (string) ($data['ResultDesc'] ?? ''),
            raw: $data,
        );
    }

    /**
     * ResultCode 0 means the customer completed the payment.
     */
    public function paid(): bool
    {
        return $this->resultCode === '0';
    }

    /**
     * ResultCode 1032 is Safaricom's "request cancelled by user".
     */
    public function cancelledByUser(): bool
    {
        return $this->resultCode === '1032';
    }
}
