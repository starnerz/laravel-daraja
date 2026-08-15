<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Data;

/**
 * A single C2B transaction returned by the Pull Transactions API.
 *
 * Unlike the rest of Daraja, this API returns lowercase keys.
 */
final readonly class PulledTransaction
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $transactionId,
        public string $date,
        public string $msisdn,
        public string $sender,
        public string $transactionType,
        public string $billReference,
        public string $amount,
        public string $organisationName,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionId: (string) ($data['transactionId'] ?? ''),
            date: (string) ($data['trxDate'] ?? ''),
            msisdn: (string) ($data['msisdn'] ?? ''),
            sender: (string) ($data['sender'] ?? ''),
            transactionType: (string) ($data['transactiontype'] ?? ''),
            billReference: (string) ($data['billreference'] ?? ''),
            amount: (string) ($data['amount'] ?? ''),
            organisationName: (string) ($data['organizationname'] ?? ''),
            raw: $data,
        );
    }
}
