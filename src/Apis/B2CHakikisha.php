<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Apis;

use Illuminate\Support\Str;
use Starnerz\LaravelDaraja\Data\B2CHakikishaResponse;
use Starnerz\LaravelDaraja\Support\PhoneNumber;

/**
 * Verify the masked identity of a customer before making a B2C payout.
 *
 * This synchronous API requires Safaricom onboarding.
 */
final class B2CHakikisha extends Api
{
    private const string ENDPOINT = 'mpesa/b2c/hakikisha/v1/hakikisha';

    public function validate(
        string $phone,
        ?string $shortCode = null,
        ?string $requestId = null,
        ?string $timestamp = null,
    ): B2CHakikishaResponse {
        $response = $this->client->post(self::ENDPOINT, [
            'header' => [
                'requestID' => $requestId ?? (string) Str::uuid(),
                'timestamp' => $timestamp ?? (string) time(),
            ],
            'body' => [
                'msisdn' => PhoneNumber::normalise($phone),
                'shortcode' => $this->initiatorShortCode($shortCode),
            ],
        ]);

        return B2CHakikishaResponse::fromArray($response);
    }
}
