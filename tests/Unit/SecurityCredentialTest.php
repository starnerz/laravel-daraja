<?php

declare(strict_types=1);

use Starnerz\LaravelDaraja\Exceptions\ConfigurationException;
use Starnerz\LaravelDaraja\Support\SecurityCredential;

beforeEach(function () {
    config()->set('laravel-daraja.certificate_path', __DIR__.'/../Fixtures/test-certificate.cer');
});

it('encrypts the configured initiator credential', function () {
    $credential = app(SecurityCredential::class)->generate();

    expect($credential)->toBeString()->not->toBeEmpty()
        // RSA-2048 output is 256 bytes, base64 encoded to 344 characters.
        ->and(strlen($credential))->toBe(344)
        ->and(base64_decode($credential, true))->not->toBeFalse();
});

it('produces a different ciphertext each time for the same password', function () {
    $credential = app(SecurityCredential::class);

    // PKCS#1 v1.5 padding is randomised, so Safaricom expects a fresh value
    // per request rather than a cached one.
    expect($credential->generate())->not->toBe($credential->generate());
});

it('encrypts an explicitly supplied password', function () {
    expect(app(SecurityCredential::class)->generate('some-other-password'))
        ->toBeString()->not->toBeEmpty();
});

it('fails clearly when no credential is configured', function () {
    config()->set('laravel-daraja.initiator.credential', null);

    app(SecurityCredential::class)->generate();
})->throws(ConfigurationException::class, 'laravel-daraja.initiator.credential');

it('fails clearly when the certificate is missing', function () {
    config()->set('laravel-daraja.certificate_path', null);
    config()->set('laravel-daraja.mode', 'sandbox');

    // sandbox.cer is deliberately not bundled; it must be downloaded from the portal.
    app(SecurityCredential::class)->certificatePath();
})->throws(ConfigurationException::class, 'developer.safaricom.co.ke');

it('picks the certificate matching the configured mode', function () {
    config()->set('laravel-daraja.certificate_path', null);
    config()->set('laravel-daraja.mode', 'live');

    expect(app(SecurityCredential::class)->certificatePath())
        ->toEndWith('production.cer');
});
