<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Starnerz\LaravelDaraja\Exceptions\AuthenticationException;
use Starnerz\LaravelDaraja\Exceptions\ConfigurationException;
use Starnerz\LaravelDaraja\Http\TokenRepository;

beforeEach(function () {
    cache()->flush();
});

it('requests a token with basic auth against the sandbox host', function () {
    Http::fake([
        'sandbox.safaricom.co.ke/oauth/*' => Http::response(payload('oauth-token')),
    ]);

    expect(app(TokenRepository::class)->token())->toBe('c9SQxWWhmdVRlyh0zh8gZDTkubVF');

    Http::assertSent(function ($request) {
        expect($request->url())->toContain('grant_type=client_credentials');

        return $request->hasHeader('Authorization', 'Basic '.base64_encode('test-consumer-key:test-consumer-secret'));
    });
});

it('caches the token instead of re-requesting it', function () {
    Http::fake([
        'sandbox.safaricom.co.ke/oauth/*' => Http::response(payload('oauth-token')),
    ]);

    $tokens = app(TokenRepository::class);

    $tokens->token();
    $tokens->token();
    $tokens->token();

    // v1 fetched a token in every constructor; v2 must hit the network once.
    Http::assertSentCount(1);
});

it('fetches again once the cached token is forgotten', function () {
    Http::fake([
        'sandbox.safaricom.co.ke/oauth/*' => Http::response(payload('oauth-token')),
    ]);

    $tokens = app(TokenRepository::class);

    $tokens->token();
    $tokens->forget();
    $tokens->token();

    Http::assertSentCount(2);
});

it('uses the live host when the mode is live', function () {
    config()->set('laravel-daraja.mode', 'live');

    Http::fake([
        'api.safaricom.co.ke/oauth/*' => Http::response(payload('oauth-token')),
    ]);

    app(TokenRepository::class)->token();

    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://api.safaricom.co.ke/'));
});

it('fails clearly when the consumer key is missing', function () {
    config()->set('laravel-daraja.consumer_key', null);

    app(TokenRepository::class)->token();
})->throws(ConfigurationException::class, 'laravel-daraja.consumer_key');

it('fails clearly when Daraja rejects the credentials', function () {
    Http::fake([
        'sandbox.safaricom.co.ke/oauth/*' => Http::response(payload('error-bad-request'), 401),
    ]);

    app(TokenRepository::class)->token();
})->throws(AuthenticationException::class);

it('fails clearly when the response has no access token', function () {
    Http::fake([
        'sandbox.safaricom.co.ke/oauth/*' => Http::response(['expires_in' => '3599']),
    ]);

    app(TokenRepository::class)->token();
})->throws(AuthenticationException::class, 'did not return an access_token');
