<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Apis;

use Illuminate\Support\Str;
use Starnerz\LaravelDaraja\Data\Acknowledgement;
use Starnerz\LaravelDaraja\Enums\CommandId;
use Starnerz\LaravelDaraja\Support\PhoneNumber;

/**
 * Business to Customer: pay money out to a customer's M-Pesa account.
 *
 * Endpoint confirmed as v3 against the Daraja portal on 15 Aug 2026.
 */
final class B2C extends Api
{
    private const ENDPOINT = 'mpesa/b2c/v3/paymentrequest';

    /**
     * Business To Pochi takes an identical payload on its own endpoint.
     */
    private const POCHI_ENDPOINT = 'mpesa/b2pochi/v1/paymentrequest';

    /**
     * Pay a salary.
     */
    public function salary(
        string $phone,
        int|float|string $amount,
        string $remarks = 'Salary payment',
        ?string $occasion = null,
        ?string $originatorConversationId = null,
    ): Acknowledgement {
        return $this->pay(
            CommandId::SalaryPayment,
            $phone,
            $amount,
            $remarks,
            $occasion,
            originatorConversationId: $originatorConversationId,
        );
    }

    /**
     * Make a general business payment, such as a refund or payout.
     */
    public function business(
        string $phone,
        int|float|string $amount,
        string $remarks = 'Business payment',
        ?string $occasion = null,
        ?string $originatorConversationId = null,
    ): Acknowledgement {
        return $this->pay(
            CommandId::BusinessPayment,
            $phone,
            $amount,
            $remarks,
            $occasion,
            originatorConversationId: $originatorConversationId,
        );
    }

    /**
     * Pay out a promotion or prize.
     */
    public function promotion(
        string $phone,
        int|float|string $amount,
        string $remarks = 'Promotion payment',
        ?string $occasion = null,
        ?string $originatorConversationId = null,
    ): Acknowledgement {
        return $this->pay(
            CommandId::PromotionPayment,
            $phone,
            $amount,
            $remarks,
            $occasion,
            originatorConversationId: $originatorConversationId,
        );
    }

    /**
     * Pay into a customer's Pochi la Biashara business wallet.
     *
     * The recipient must have a Pochi la Biashara (micro-SME) wallet.
     */
    public function pochi(
        string $phone,
        int|float|string $amount,
        string $remarks = 'Pochi payment',
        ?string $occasion = null,
        ?string $originatorConversationId = null,
    ): Acknowledgement {
        return $this->pay(
            CommandId::BusinessPayToPochi,
            $phone,
            $amount,
            $remarks,
            $occasion,
            originatorConversationId: $originatorConversationId,
            endpoint: self::POCHI_ENDPOINT,
        );
    }

    /**
     * @param  string|null  $originatorConversationId  Unique per request; Safaricom
     *                                                 uses it to reject duplicate
     *                                                 disbursements. Generated if omitted.
     */
    public function pay(
        CommandId $command,
        string $phone,
        int|float|string $amount,
        string $remarks,
        ?string $occasion = null,
        ?string $shortCode = null,
        ?string $originatorConversationId = null,
        ?string $endpoint = null,
    ): Acknowledgement {
        $response = $this->client->post($endpoint ?? self::ENDPOINT, [
            // Required from v3 onwards — guards against double disbursement.
            'OriginatorConversationID' => $originatorConversationId ?? (string) Str::uuid(),
            'InitiatorName' => $this->initiatorName(),
            'SecurityCredential' => $this->credential->generate(),
            'CommandID' => $command->value,
            'Amount' => $this->amount($amount),
            'PartyA' => $this->initiatorShortCode($shortCode),
            'PartyB' => PhoneNumber::normalise($phone),
            'Remarks' => Str::limit($remarks, 100, ''),
            'QueueTimeOutURL' => $this->urls->resolve(
                $this->setting('urls.timeout.b2c'),
                'urls.timeout.b2c',
            ),
            'ResultURL' => $this->urls->resolve(
                $this->setting('urls.result.b2c'),
                'urls.result.b2c',
            ),
            // Safaricom's own spelling of "Occasion".
            'Occassion' => Str::limit($occasion ?? '', 100, ''),
        ]);

        return Acknowledgement::fromArray($response);
    }
}
