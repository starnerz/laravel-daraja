<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Apis;

use DateTimeInterface;
use Illuminate\Support\Str;
use Starnerz\LaravelDaraja\Data\StandingOrderResponse;
use Starnerz\LaravelDaraja\Enums\IdentifierType;
use Starnerz\LaravelDaraja\Enums\StandingOrderFrequency;
use Starnerz\LaravelDaraja\Support\PhoneNumber;

/**
 * M-Pesa Ratiba: create standing orders for recurring collections.
 *
 * Confirmed against the Daraja portal on 15 Aug 2026. This is a commercial API
 * requiring a signed agreement before go-live, and it sits outside the /mpesa/
 * path used by the rest of Daraja.
 */
final class StandingOrder extends Api
{
    private const ENDPOINT = 'standingorder/v1/createStandingOrderExternal';

    private const PAY_BILL = 'Standing Order Pay Bill External Third Party';

    private const BUY_GOODS = 'Standing Order Pay Merchant External Third Party';

    /**
     * Create a standing order on a customer's M-Pesa wallet.
     *
     * The customer receives an STK prompt to authorise it.
     *
     * @param  string  $name  Must be unique per customer — a customer cannot hold
     *                        two standing orders with the same name (error 1050)
     * @param  string|null  $customStoId  Partner-generated UUID echoed back in the
     *                                    callback; generated if omitted
     */
    public function create(
        string $name,
        string $phone,
        int|float|string $amount,
        DateTimeInterface|string $startDate,
        DateTimeInterface|string $endDate,
        string $accountReference,
        StandingOrderFrequency $frequency = StandingOrderFrequency::Monthly,
        ?string $description = null,
        ?string $callbackUrl = null,
        ?string $shortCode = null,
        ?IdentifierType $receiverType = null,
        ?string $customStoId = null,
    ): StandingOrderResponse {
        $receiverType ??= IdentifierType::fromConfig($this->setting('initiator.type'));

        $response = $this->client->post(self::ENDPOINT, [
            'StandingOrderName' => $name,
            'StartDate' => $this->formatDate($startDate),
            'EndDate' => $this->formatDate($endDate),
            'BusinessShortCode' => $this->initiatorShortCode($shortCode),
            'TransactionType' => $receiverType === IdentifierType::Till
                ? self::BUY_GOODS
                : self::PAY_BILL,
            'ReceiverPartyIdentifierType' => (string) $receiverType->value,
            'CustomStoId' => $customStoId ?? (string) Str::uuid(),
            'Amount' => $this->amount($amount),
            'PartyA' => PhoneNumber::normalise($phone),
            'CallBackURL' => $this->urls->resolve(
                $callbackUrl ?? $this->setting('urls.result.standing_order'),
                'urls.result.standing_order',
            ),
            'AccountReference' => Str::limit($accountReference, 12, ''),
            'TransactionDesc' => Str::limit($description ?? $accountReference, 13, ''),
            'Frequency' => $frequency->value,
        ]);

        return StandingOrderResponse::fromArray($response);
    }

    private function formatDate(DateTimeInterface|string $date): string
    {
        return $date instanceof DateTimeInterface ? $date->format('Ymd') : $date;
    }
}
