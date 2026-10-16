<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Starnerz\LaravelDaraja\Exceptions\ApiRequestException;
use Starnerz\LaravelDaraja\Exceptions\AuthenticationException;
use Starnerz\LaravelDaraja\Exceptions\ConfigurationException;
use Starnerz\LaravelDaraja\Exceptions\DarajaException;
use Starnerz\LaravelDaraja\Facades\Daraja;

beforeEach(function () {
    cache()->flush();
});

it('keeps Safaricom\'s message and puts the docs in the log context', function () {
    fakeDaraja([
        '*/mpesa/stkpush/*' => Http::response([
            'requestId' => 'req-1',
            'errorCode' => '400.002.02',
            'errorMessage' => 'Bad Request - Invalid Amount',
        ], 400),
    ]);

    try {
        Daraja::stk()->push('0712345678', 100, 'INV-1');
        $this->fail('Expected an ApiRequestException.');
    } catch (ApiRequestException $e) {
        expect($e->getMessage())->toBe('Safaricom Daraja: Bad Request - Invalid Amount')
            ->and($e->docsUrl())->toBe('https://laraveldaraja.com/reference/error-codes/')
            ->and($e->context())->toBe([
                'docs' => 'https://laraveldaraja.com/reference/error-codes/',
                'error_code' => '400.002.02',
                'request_id' => 'req-1',
                'status' => 400,
            ]);
    }
});

it('leaves out identifiers Safaricom did not send', function () {
    $e = new ApiRequestException('Safaricom Daraja: down');

    expect($e->context())->toBe(['docs' => 'https://laraveldaraja.com/reference/error-codes/']);
});

it('links every package exception to the exceptions reference by default', function () {
    $e = new DarajaException('At least one invoice is required.');

    expect($e->context())->toBe(['docs' => 'https://laraveldaraja.com/reference/exceptions/']);
});

it('points a missing setting at the configuration reference', function () {
    $e = ConfigurationException::missing('stk.pass_key');

    expect($e->getMessage())->toContain('See https://laraveldaraja.com/reference/configuration/')
        ->and($e->docsUrl())->toBe('https://laraveldaraja.com/reference/configuration/');
});

it('points a failed token request at the credentials setup', function () {
    expect(AuthenticationException::tokenRequestFailed('Invalid client.')->getMessage())
        ->toBe('Could not obtain a Daraja access token: Invalid client. See https://laraveldaraja.com/getting-started/configuration/')
        ->and(AuthenticationException::malformedTokenResponse()->getMessage())
        ->toEndWith('See https://laraveldaraja.com/getting-started/configuration/');
});

it('suggests the first tutorial once the credentials work', function () {
    fakeDaraja();

    $this->artisan('daraja:token')
        ->expectsOutputToContain('https://laraveldaraja.com/tutorials/stk-push/')
        ->assertSuccessful();
});
