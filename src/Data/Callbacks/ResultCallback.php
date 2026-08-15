<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Data\Callbacks;

use Starnerz\LaravelDaraja\Support\AccountBalances;
use Starnerz\LaravelDaraja\Support\ResultParameters;

/**
 * The shared "Result" envelope used by B2C, B2B, Account Balance, Transaction
 * Status, Reversal and Business To Pochi.
 *
 * ResultParameters is absent on failure and its inner list degrades to a single
 * object; ResultParameters::make() absorbs both.
 */
final readonly class ResultCallback
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $resultType,
        public string $resultCode,
        public string $resultDescription,
        public string $originatorConversationId,
        public string $conversationId,
        public string $transactionId,
        public ResultParameters $parameters,
        public ResultParameters $reference,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $result = $payload['Result'] ?? $payload;
        $result = is_array($result) ? $result : [];

        return new self(
            resultType: (string) ($result['ResultType'] ?? ''),
            // Numeric on B2C, a string like "R000002" on Reversal.
            resultCode: (string) ($result['ResultCode'] ?? ''),
            resultDescription: (string) ($result['ResultDesc'] ?? ''),
            originatorConversationId: (string) ($result['OriginatorConversationID'] ?? ''),
            conversationId: (string) ($result['ConversationID'] ?? ''),
            transactionId: (string) ($result['TransactionID'] ?? ''),
            parameters: ResultParameters::make($result['ResultParameters']['ResultParameter'] ?? null),
            reference: ResultParameters::make($result['ReferenceData']['ReferenceItem'] ?? null),
            raw: $payload,
        );
    }

    public function successful(): bool
    {
        return $this->resultCode === '0';
    }

    /**
     * A placeholder receipt is returned on certain failures.
     */
    public function hasRealTransactionId(): bool
    {
        return $this->transactionId !== ''
            && ! preg_match('/^[A-Z]{2,3}0{7,}$/', $this->transactionId);
    }

    public function amount(): float
    {
        return $this->parameters->has('TransactionAmount')
            ? $this->parameters->float('TransactionAmount')
            : $this->parameters->float('Amount');
    }

    public function receipt(): string
    {
        return $this->parameters->has('TransactionReceipt')
            ? $this->parameters->string('TransactionReceipt')
            : $this->parameters->string('ReceiptNo');
    }

    /**
     * Present on B2C and Business To Pochi results.
     */
    public function receiverName(): string
    {
        return $this->parameters->string('ReceiverPartyPublicName');
    }

    public function recipientIsRegistered(): ?bool
    {
        if (! $this->parameters->has('B2CRecipientIsRegisteredCustomer')) {
            return null;
        }

        return strtoupper($this->parameters->string('B2CRecipientIsRegisteredCustomer')) === 'Y';
    }

    /**
     * Account Balance packs every account into one string under this key.
     */
    public function balances(): AccountBalances
    {
        return AccountBalances::parse($this->parameters->string('AccountBalance'));
    }

    /**
     * Transaction Status returns DebitPartyName twice — once per side of the
     * transaction — so both are returned here rather than the first only.
     *
     * @return list<mixed>
     */
    public function partyNames(): array
    {
        return $this->parameters->all('DebitPartyName');
    }
}
