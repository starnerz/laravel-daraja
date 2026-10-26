<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Starnerz\LaravelDaraja\Data\MobileNumberValidationResponse;
use Starnerz\LaravelDaraja\Enums\MobileIdType;
use Starnerz\LaravelDaraja\Exceptions\DarajaException;
use Starnerz\LaravelDaraja\Facades\Daraja;

beforeEach(function () {
    cache()->flush();
    fakeDaraja([
        '*/KYC-validation/validateID' => Http::response(payload('mobile-number-validation-match')),
        '*/imsi/v2/checkATI' => Http::response(payload('sim-swap')),
        // Not yet checked against the portal; see docs/api-specs/identity-and-hakikisha.md.
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
        ->and($response->responseRefId)->toBe('1771929697')
        ->and($response->responseMessage)->toBe('Details match successfully');

    expect(identitySentBody('KYC-validation/validateID'))->toMatchArray([
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

it('reports a mobile identity mismatch', function () {
    $response = MobileNumberValidationResponse::fromArray(payload('mobile-number-validation-mismatch'));

    expect($response->matches())->toBeFalse()
        ->and($response->status)->toBe('false');
});

it('treats the response code as authoritative over status', function () {
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
        ->and($response->lastSwapDate)->toBe('01-01-1900 00:00')
        ->and(identitySentBody('imsi/v2/checkATI'))->toBe(['customerNumber' => '254712345678']);
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
        ->and($response->fullName())->toBe('Jane M****** K******')
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

it('calls the identity APIs with the settings of Daraja::using()', function () {
    Daraja::using(['initiator.short_code' => '777777'])->mobileNumberValidation()
        ->validate('0712345678', MobileIdType::NationalId, '12345678');

    expect(identitySentBody('KYC-validation/validateID')['shortCode'])->toBe('777777');

    Daraja::using(['initiator.short_code' => '888888'])->b2cHakikisha()
        ->validate('0712345678');

    expect(identitySentBody('b2c/hakikisha/v1/hakikisha')['body']['shortcode'])->toBe('888888');
});

it('rejects invalid phone numbers across identity endpoints', function (string $phone) {
    expect(fn () => Daraja::simSwap()->query($phone))->toThrow(DarajaException::class);
    expect(fn () => Daraja::b2cHakikisha()->validate($phone))->toThrow(DarajaException::class);
    expect(fn () => Daraja::mobileNumberValidation()->validate($phone, MobileIdType::NationalId, '12345678'))
        ->toThrow(DarajaException::class);
})->with(['not-a-number', '254812345678', '0712']);

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
