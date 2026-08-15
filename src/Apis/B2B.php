<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Apis;

use Illuminate\Support\Str;
use Starnerz\LaravelDaraja\Data\Acknowledgement;
use Starnerz\LaravelDaraja\Enums\CommandId;
use Starnerz\LaravelDaraja\Enums\IdentifierType;
use Starnerz\LaravelDaraja\Support\PhoneNumber;

/**
 * Business to Business: pay another business from your own short code.
 *
 * Confirmed against the Daraja portal on 15 Aug 2026. Both Business Pay Bill and
 * Business Buy Goods use the same endpoint and differ only by CommandID. Money
 * moves from your MMF/Working account to the recipient's utility (pay bill) or
 * merchant (buy goods) account.
 */
final class B2B extends Api
{
    private const ENDPOINT = 'mpesa/b2b/v1/paymentrequest';

    /**
     * Pay another business's pay bill.
     *
     * @param  string|null  $requester  Mobile number of the consumer you are paying
     *                                  on behalf of, if any.
     */
    public function payBill(
        string $receiverShortCode,
        int|float|string $amount,
        string $accountReference,
        string $remarks = 'Business pay bill',
        ?string $requester = null,
        ?string $senderShortCode = null,
    ): Acknowledgement {
        return $this->pay(
            CommandId::BusinessPayBill,
            $receiverShortCode,
            $amount,
            $accountReference,
            $remarks,
            $requester,
            $senderShortCode,
        );
    }

    /**
     * Pay another business's till, merchant store number or merchant HO.
     */
    public function buyGoods(
        string $receiverShortCode,
        int|float|string $amount,
        string $accountReference = '',
        string $remarks = 'Business buy goods',
        ?string $requester = null,
        ?string $senderShortCode = null,
    ): Acknowledgement {
        return $this->pay(
            CommandId::BusinessBuyGoods,
            $receiverShortCode,
            $amount,
            $accountReference,
            $remarks,
            $requester,
            $senderShortCode,
        );
    }

    /**
     * Load funds into one of your B2C short codes so it can disburse.
     *
     * Requires the "Org Business Pay to Bulk API initiator" role, which is
     * distinct from the Pay Bill and Buy Goods initiator roles.
     */
    public function accountTopUp(
        string $b2cShortCode,
        int|float|string $amount,
        string $accountReference = '',
        string $remarks = 'B2C account top up',
        ?string $requester = null,
        ?string $senderShortCode = null,
    ): Acknowledgement {
        return $this->pay(
            CommandId::BusinessPayToBulk,
            $b2cShortCode,
            $amount,
            $accountReference,
            $remarks,
            $requester,
            $senderShortCode,
        );
    }

    public function pay(
        CommandId $command,
        string $receiverShortCode,
        int|float|string $amount,
        string $accountReference,
        string $remarks,
        ?string $requester = null,
        ?string $senderShortCode = null,
    ): Acknowledgement {
        $payload = [
            'Initiator' => $this->initiatorName(),
            'SecurityCredential' => $this->credential->generate(),
            'CommandID' => $command->value,
            // Safaricom accepts only type 4 on both sides of this API, regardless
            // of whether the destination is a pay bill or a till.
            'SenderIdentifierType' => (string) IdentifierType::ShortCode->value,
            // Safaricom's own spelling of "ReceiverIdentifierType".
            'RecieverIdentifierType' => (string) IdentifierType::ShortCode->value,
            'Amount' => $this->amount($amount),
            'PartyA' => $this->initiatorShortCode($senderShortCode),
            'PartyB' => $receiverShortCode,
            'AccountReference' => Str::limit($accountReference, 13, ''),
            'Remarks' => Str::limit($remarks, 100, ''),
            'QueueTimeOutURL' => $this->urls->resolve(
                $this->setting('urls.timeout.b2b'),
                'urls.timeout.b2b',
            ),
            'ResultURL' => $this->urls->resolve(
                $this->setting('urls.result.b2b'),
                'urls.result.b2b',
            ),
        ];

        if (filled($requester)) {
            $payload['Requester'] = PhoneNumber::normalise($requester);
        }

        return Acknowledgement::fromArray($this->client->post(self::ENDPOINT, $payload));
    }
}
