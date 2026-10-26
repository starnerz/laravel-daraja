<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Apis;

use Starnerz\LaravelDaraja\Data\SwapResponse;
use Starnerz\LaravelDaraja\Support\PhoneNumber;

/**
 * Query the most recent SIM swap date for a Safaricom mobile number.
 */
final class Swap extends Api
{
    private const string ENDPOINT = 'imsi/v2/checkATI';

    public function query(string $phone): SwapResponse
    {
        $response = $this->client->post(self::ENDPOINT, [
            'customerNumber' => PhoneNumber::normalise($phone),
        ]);

        return SwapResponse::fromArray($response);
    }
}
