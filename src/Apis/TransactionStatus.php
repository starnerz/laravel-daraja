<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Apis;

use Illuminate\Support\Str;
use Starnerz\LaravelDaraja\Data\Acknowledgement;
use Starnerz\LaravelDaraja\Enums\CommandId;
use Starnerz\LaravelDaraja\Enums\IdentifierType;
use Starnerz\LaravelDaraja\Exceptions\DarajaException;

/**
 * Check the status of any M-Pesa transaction — C2B, B2B, B2C, IMT or Reversal.
 *
 * Confirmed against the Daraja portal on 15 Aug 2026. Useful as a secondary
 * reconciliation mechanism when a callback never arrives. The outcome is
 * delivered to your result URL, not returned here.
 */
final class TransactionStatus extends Api
{
    private const ENDPOINT = 'mpesa/transactionstatus/v1/query';

    /**
     * Query by M-Pesa receipt number.
     *
     * @param  string  $transactionId  The M-Pesa receipt, e.g. "NEF61H8J60"
     */
    public function query(
        string $transactionId,
        string $remarks = 'Transaction status query',
        ?string $occasion = null,
        ?string $shortCode = null,
        ?IdentifierType $identifierType = null,
    ): Acknowledgement {
        return $this->send($transactionId, null, $remarks, $occasion, $shortCode, $identifierType);
    }

    /**
     * Query by the OriginatorConversationID returned when the transaction was
     * created — useful when you never received a receipt number.
     */
    public function queryByConversationId(
        string $originalConversationId,
        string $remarks = 'Transaction status query',
        ?string $occasion = null,
        ?string $shortCode = null,
        ?IdentifierType $identifierType = null,
    ): Acknowledgement {
        return $this->send(null, $originalConversationId, $remarks, $occasion, $shortCode, $identifierType);
    }

    private function send(
        ?string $transactionId,
        ?string $originalConversationId,
        string $remarks,
        ?string $occasion,
        ?string $shortCode,
        ?IdentifierType $identifierType,
    ): Acknowledgement {
        if (blank($transactionId) && blank($originalConversationId)) {
            throw new DarajaException(
                'Provide either an M-Pesa receipt number or an OriginalConversationID to query a transaction.',
            );
        }

        $identifierType ??= IdentifierType::fromConfig($this->setting('initiator.type'));

        $payload = [
            'Initiator' => $this->initiatorName(),
            'SecurityCredential' => $this->credential->generate(),
            'CommandID' => CommandId::TransactionStatusQuery->value,
            'PartyA' => $this->initiatorShortCode($shortCode),
            'IdentifierType' => (string) $identifierType->value,
            'ResultURL' => $this->urls->resolve(
                $this->setting('urls.result.transaction_status'),
                'urls.result.transaction_status',
            ),
            'QueueTimeOutURL' => $this->urls->resolve(
                $this->setting('urls.timeout.transaction_status'),
                'urls.timeout.transaction_status',
            ),
            'Remarks' => Str::limit($remarks, 100, ''),
            // This API spells it "Occasion" with one "s", unlike B2C and Reversal.
            'Occasion' => Str::limit($occasion ?? '', 100, ''),
        ];

        if (filled($transactionId)) {
            $payload['TransactionID'] = $transactionId;
        }

        if (filled($originalConversationId)) {
            $payload['OriginalConversationID'] = $originalConversationId;
        }

        return Acknowledgement::fromArray($this->client->post(self::ENDPOINT, $payload));
    }
}
