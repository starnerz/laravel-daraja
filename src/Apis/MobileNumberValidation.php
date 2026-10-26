<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Apis;

use Illuminate\Support\Str;
use Starnerz\LaravelDaraja\Data\MobileNumberValidationResponse;
use Starnerz\LaravelDaraja\Enums\MobileIdType;
use Starnerz\LaravelDaraja\Exceptions\ApiRequestException;
use Starnerz\LaravelDaraja\Exceptions\DarajaException;
use Starnerz\LaravelDaraja\Support\PhoneNumber;

/**
 * Check whether a Safaricom number is registered to the supplied identity.
 *
 * This commercial API must be enabled for the Daraja app by Safaricom.
 */
final class MobileNumberValidation extends Api
{
    private const string ENDPOINT = 'v1/KYC-validation/validateID';

    public function validate(
        string $phone,
        MobileIdType $idType,
        string $idNumber,
        ?string $shortCode = null,
        ?string $requestRefId = null,
    ): MobileNumberValidationResponse {
        if (blank(trim($idNumber))) {
            throw new DarajaException('An identity document number is required for Mobile Number Validation.');
        }

        $response = $this->client->post(self::ENDPOINT, [
            'requestRefID' => $requestRefId ?? (string) Str::uuid(),
            'shortCode' => $this->initiatorShortCode($shortCode),
            'msisdn' => PhoneNumber::normalise($phone),
            'idType' => $idType->value,
            'idNumber' => trim($idNumber),
        ]);

        if (isset($response['errorCode'])) {
            throw ApiRequestException::fromPayload($response);
        }

        return MobileNumberValidationResponse::fromArray($response);
    }
}
