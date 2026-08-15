<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Data\Callbacks;

use Illuminate\Support\Str;

/**
 * A customer payment delivered to your C2B validation or confirmation URL.
 *
 * On validation requests OrgAccountBalance is blank; on confirmation it holds
 * the new balance after the payment.
 */
final readonly class C2BTransaction
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $transactionType,
        public string $transactionId,
        public string $transactionTime,
        public string $amount,
        public string $businessShortCode,
        public string $billReferenceNumber,
        public string $invoiceNumber,
        public string $organisationAccountBalance,
        public string $thirdPartyTransactionId,
        public string $msisdn,
        public string $firstName,
        public string $middleName,
        public string $lastName,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            transactionType: (string) ($payload['TransactionType'] ?? ''),
            transactionId: (string) ($payload['TransID'] ?? ''),
            transactionTime: (string) ($payload['TransTime'] ?? ''),
            amount: (string) ($payload['TransAmount'] ?? ''),
            businessShortCode: (string) ($payload['BusinessShortCode'] ?? ''),
            billReferenceNumber: (string) ($payload['BillRefNumber'] ?? ''),
            invoiceNumber: (string) ($payload['InvoiceNumber'] ?? ''),
            organisationAccountBalance: (string) ($payload['OrgAccountBalance'] ?? ''),
            thirdPartyTransactionId: (string) ($payload['ThirdPartyTransID'] ?? ''),
            // v2 masks this ("2547 ***** 126"); v1 sent a SHA-256 hash.
            msisdn: (string) ($payload['MSISDN'] ?? ''),
            firstName: (string) ($payload['FirstName'] ?? ''),
            middleName: (string) ($payload['MiddleName'] ?? ''),
            lastName: (string) ($payload['LastName'] ?? ''),
            raw: $payload,
        );
    }

    public function fullName(): string
    {
        return Str::squish("{$this->firstName} {$this->middleName} {$this->lastName}");
    }

    public function isPayBill(): bool
    {
        return Str::contains($this->transactionType, 'Pay Bill', ignoreCase: true);
    }

    /**
     * Validation requests carry no balance; confirmations do.
     */
    public function isConfirmation(): bool
    {
        return $this->organisationAccountBalance !== '';
    }
}
