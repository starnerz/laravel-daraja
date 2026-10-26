<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Starnerz\LaravelDaraja\Data\MobileNumberValidationResponse;
use Starnerz\LaravelDaraja\Data\SwapResponse;
use Starnerz\LaravelDaraja\Enums\MobileIdType;
use Starnerz\LaravelDaraja\Exceptions\DarajaException;
use Starnerz\LaravelDaraja\Facades\Daraja;

beforeEach(function () {
    cache()->flush();
    fakeDaraja([
        '*/KYC-validation/validateID' => Http::response([
            'responseRefID' => 'request-1',
            'responseCode' => '4000',
            'responseMessage' => 'Match found',
            'status' => true,
        ]),
        '*/imsi/v2/checkATI' => Http::response([
            'requestRefID' => 'request-2',
            'responseCode' => '0',
            'responseDesc' => 'Success',
            'lastSwapDate' => '2026-01-15',
        ]),
        '*/mpesa/b2c/hakikisha/v1/hakikisha' => Http::response([
            'header' => [
                'requestID' => 'request-3',
                'timestamp' => '1793000000',
                'status' => '200',
                'message' => 'Success',
            ],
            'body' => [
                'firstName' => 'Jane',
                'middleName' => 'M******',
                'lastName' => 'K******',
            ],
        ]),
    ]);
});

it('validates a Safaricom number against an identity document', function () {
    $response = Daraja::mobileNumberValidation()->validate(
        '0712345678',
        MobileIdType::NationalId,
        '12345678',
        requestRefId: 'my-request',
    );

    expect($response->matches())->toBeTrue()
        ->and($response->responseMessage)->toBe('Match found');

    $body = identitySentBody('KYC-validation/validateID');

    expect($body)->toMatchArray([
        'requestRefID' => 'my-request',
        'shortCode' => '600000',
        'msisdn' => '254712345678',
        'idType' => '01',
        'idNumber' => '12345678',
    ]);
});

it('generates a request reference for mobile number validation', function () {
    Daraja::mobileNumberValidation()->validate('254712345678', MobileIdType::Passport, 'P123456');

    expect(identitySentBody('KYC-validation/validateID')['requestRefID'])->not->toBeEmpty()
        ->and(identitySentBody('KYC-validation/validateID')['idType'])->toBe('05');
});

it('parses the documented mobile identity mismatch response', function () {
    $response = MobileNumberValidationResponse::fromArray([
        'responseRefID' => 'response-2',
        'responseCode' => '4001',
        'responseMessage' => 'Details do not match',
        'status' => 'false',
    ]);

    expect($response->matches())->toBeFalse()
        ->and($response->responseRefId)->toBe('response-2')
        ->and($response->responseCode)->toBe('4001')
        ->and($response->responseMessage)->toBe('Details do not match');
});

it('treats the mismatch response code as authoritative', function () {
    $response = MobileNumberValidationResponse::fromArray([
        'responseCode' => '4001',
        'status' => true,
    ]);

    expect($response->matches())->toBeFalse();
});

it('rejects an empty identity document number', function () {
    Daraja::mobileNumberValidation()->validate('0712345678', MobileIdType::NationalId, ' ');
})->throws(DarajaException::class, 'identity document number');

it('queries the last SIM swap date', function () {
    $response = Daraja::simSwap()->query('0712345678');

    expect($response->successful())->toBeTrue()
        ->and($response->lastSwapDate)->toBe('2026-01-15')
        ->and(identitySentBody('imsi/v2/checkATI'))->toBe(['customerNumber' => '254712345678']);
});

it('accepts Safaricom documented success response codes for SIM swap checks', function () {
    $response = SwapResponse::fromArray([
        'responseCode' => '200',
        'responseDesc' => 'Success',
    ]);

    expect($response->successful())->toBeTrue();
});

it('checks a recipient name before a B2C payout', function () {
    $response = Daraja::b2cHakikisha()->validate(
        '0712345678',
        requestId: 'my-request',
        timestamp: '1793000000',
    );

    expect($response->successful())->toBeTrue()
        ->and($response->firstName)->toBe('Jane')
        ->and($response->middleName)->toBe('M******')
        ->and(identitySentBody('b2c/hakikisha/v1/hakikisha'))->toBe([
            'header' => ['requestID' => 'my-request', 'timestamp' => '1793000000'],
            'body' => ['msisdn' => '254712345678', 'shortcode' => '600000'],
        ]);

});

it('generates the B2C Hakikisha request identifiers', function () {
    Daraja::b2cHakikisha()->validate('254712345678');

    $body = identitySentBody('b2c/hakikisha/v1/hakikisha');

    expect($body['header']['requestID'])->not->toBeEmpty()
        ->and($body['header']['timestamp'])->toMatch('/^\d+$/');
});

it('rejects a shortcode outside the B2C Hakikisha format', function () {
    Daraja::b2cHakikisha()->validate('0712345678', shortCode: '12A');
})->throws(DarajaException::class, 'shortcode of 5 to 7 digits');

/**
 * @return array<string, mixed>
 */
function identitySentBody(string $needle): array
{
    $body = [];

    Http::assertSent(function ($request) use ($needle, &$body): bool {
        if (! str_contains($request->url(), $needle) || str_contains($request->url(), 'oauth')) {
            return false;
        }

        $body = $request->data();

        return true;
    });

    return $body;
}
