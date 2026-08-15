<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Apis;

use Starnerz\LaravelDaraja\Data\BongaResponse;
use Starnerz\LaravelDaraja\Support\PhoneNumber;

/**
 * Lipa na Bonga: accept Safaricom loyalty points as payment into your pay bill
 * or till.
 *
 * Confirmed against the Daraja portal on 15 Aug 2026. Points redeem at
 * KES 0.20 each. On success M-Pesa transfers the funds to your short code and
 * the result lands on your **C2B** confirmation URL — this API does not take a
 * callback URL of its own.
 */
final class LipaNaBonga extends Api
{
    private const CALCULATE_ENDPOINT = 'v1/lipa/na/bonga/calculate-points';

    private const REDEEM_ENDPOINT = 'v1/lipa/na/bonga/redeem-paybill';

    /**
     * Convert a number of Bonga points into their shilling value.
     */
    public function calculatePoints(int $points): BongaResponse
    {
        return BongaResponse::fromArray(
            $this->client->post(self::CALCULATE_ENDPOINT, [
                'points' => (string) $points,
            ]),
        );
    }

    /**
     * Redeem a customer's points against your short code.
     *
     * The customer receives an STK prompt to authorise with their M-Pesa PIN.
     */
    public function redeem(
        string $phone,
        int|float|string $amount,
        int $bongaPoints,
        string $accountNumber,
        float $conversionRate = 0.2,
        ?string $shortCode = null,
    ): BongaResponse {
        return BongaResponse::fromArray(
            $this->client->post(self::REDEEM_ENDPOINT, [
                'msisdn' => PhoneNumber::normalise($phone),
                'amount' => (int) $this->amount($amount),
                'bongaPoints' => $bongaPoints,
                'conversionRate' => $conversionRate,
                'shortCode' => $this->initiatorShortCode($shortCode),
                'accountNumber' => $accountNumber,
            ]),
        );
    }
}
