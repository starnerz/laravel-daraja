<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Data\Callbacks;

use Starnerz\LaravelDaraja\Support\ResultParameters;

/**
 * The result of an M-Pesa Express (STK Push) prompt, delivered to your callback.
 *
 * CallbackMetadata is absent entirely when the payment did not succeed, so
 * every accessor below tolerates its absence.
 */
final readonly class StkCallback
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $merchantRequestId,
        public string $checkoutRequestId,
        public string $resultCode,
        public string $resultDescription,
        public ResultParameters $metadata,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $callback = $payload['Body']['stkCallback'] ?? $payload;
        $callback = is_array($callback) ? $callback : [];

        return new self(
            merchantRequestId: (string) ($callback['MerchantRequestID'] ?? ''),
            checkoutRequestId: (string) ($callback['CheckoutRequestID'] ?? ''),
            // Numeric in the callback but a string in the query response, so it
            // is cast here and compared as a string throughout.
            resultCode: (string) ($callback['ResultCode'] ?? ''),
            resultDescription: (string) ($callback['ResultDesc'] ?? ''),
            metadata: ResultParameters::make($callback['CallbackMetadata']['Item'] ?? null),
            raw: $payload,
        );
    }

    public function successful(): bool
    {
        return $this->resultCode === '0';
    }

    /**
     * 1032 is Safaricom's "request cancelled by user".
     */
    public function cancelledByUser(): bool
    {
        return $this->resultCode === '1032';
    }

    /**
     * 1037 means the prompt never reached the handset.
     */
    public function timedOut(): bool
    {
        return in_array($this->resultCode, ['1037', '1031'], true);
    }

    public function amount(): float
    {
        return $this->metadata->float('Amount');
    }

    public function receipt(): string
    {
        return $this->metadata->string('MpesaReceiptNumber');
    }

    public function phoneNumber(): string
    {
        return $this->metadata->string('PhoneNumber');
    }

    /**
     * Raw YmdHis timestamp as Safaricom sends it.
     */
    public function transactionDate(): string
    {
        return $this->metadata->string('TransactionDate');
    }
}
