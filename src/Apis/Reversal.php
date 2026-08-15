<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Apis;

use Illuminate\Support\Str;
use Starnerz\LaravelDaraja\Data\Acknowledgement;
use Starnerz\LaravelDaraja\Enums\CommandId;

/**
 * Reverse a Customer to Business transaction.
 *
 * Confirmed against the Daraja portal on 15 Aug 2026.
 *
 * Note: B2C payouts cannot be reversed through this API — Safaricom requires
 * those to be reversed manually on the M-Pesa organisation portal.
 */
final class Reversal extends Api
{
    private const ENDPOINT = 'mpesa/reversal/v1/request';

    /**
     * Safaricom requires the literal value 11 on this API, unlike every other
     * endpoint where the identifier type varies by short code type.
     */
    private const RECEIVER_IDENTIFIER_TYPE = '11';

    /**
     * @param  string  $transactionId  The M-Pesa receipt being reversed
     * @param  string  $remarks  Required, 2–100 characters
     */
    public function reverse(
        string $transactionId,
        int|float|string $amount,
        string $remarks = 'Transaction reversal',
        ?string $receiverParty = null,
    ): Acknowledgement {
        $response = $this->client->post(self::ENDPOINT, [
            'Initiator' => $this->initiatorName(),
            'SecurityCredential' => $this->credential->generate(),
            'CommandID' => CommandId::TransactionReversal->value,
            'TransactionID' => $transactionId,
            'Amount' => $this->amount($amount),
            'ReceiverParty' => $this->initiatorShortCode($receiverParty),
            // Safaricom's own spelling of "ReceiverIdentifierType".
            'RecieverIdentifierType' => self::RECEIVER_IDENTIFIER_TYPE,
            'ResultURL' => $this->urls->resolve(
                $this->setting('urls.result.reversal'),
                'urls.result.reversal',
            ),
            'QueueTimeOutURL' => $this->urls->resolve(
                $this->setting('urls.timeout.reversal'),
                'urls.timeout.reversal',
            ),
            'Remarks' => Str::limit($remarks, 100, ''),
        ]);

        return Acknowledgement::fromArray($response);
    }
}
