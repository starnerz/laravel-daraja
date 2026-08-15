<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Starnerz\LaravelDaraja\Enums\Mode;
use Starnerz\LaravelDaraja\Exceptions\ApiRequestException;
use Starnerz\LaravelDaraja\Exceptions\ConfigurationException;
use Starnerz\LaravelDaraja\Facades\Daraja;
use Starnerz\LaravelDaraja\Support\CallbackUrl;
use Starnerz\LaravelDaraja\Support\SecurityCredential;

beforeEach(function () {
    cache()->flush();
});

it('reads a SOAP style fault envelope', function () {
    fakeDaraja([
        '*/mpesa/stkpush/*' => Http::response([
            'Envelope' => ['Body' => ['Fault' => ['faultstring' => 'Gateway unavailable']]],
        ], 500),
    ]);

    expect(fn () => Daraja::stk()->push('0712345678', 100, 'INV-1'))
        ->toThrow(ApiRequestException::class, 'Gateway unavailable');
});

it('falls back to ResponseDescription then ResultDesc', function (array $body, string $expected) {
    fakeDaraja(['*/mpesa/stkpush/*' => Http::response($body, 400)]);

    expect(fn () => Daraja::stk()->push('0712345678', 100, 'INV-1'))
        ->toThrow(ApiRequestException::class, $expected);
})->with([
    [['ResponseDescription' => 'Invalid short code'], 'Invalid short code'],
    [['ResultDesc' => 'Declined by limit rule'], 'Declined by limit rule'],
]);

it('handles a non-JSON error body', function () {
    fakeDaraja(['*/mpesa/stkpush/*' => Http::response('<html>502 Bad Gateway</html>', 502)]);

    expect(fn () => Daraja::stk()->push('0712345678', 100, 'INV-1'))
        ->toThrow(ApiRequestException::class, '502 Bad Gateway');
});

it('truncates a very long error body', function () {
    fakeDaraja(['*/mpesa/stkpush/*' => Http::response(str_repeat('x', 900), 500)]);

    try {
        Daraja::stk()->push('0712345678', 100, 'INV-1');
    } catch (ApiRequestException $e) {
        expect(mb_strlen($e->getMessage()))->toBeLessThan(400)
            ->and($e->getMessage())->toEndWith('…');
    }
});

it('wraps a connection failure', function () {
    fakeDaraja(['*/mpesa/stkpush/*' => fn () => throw new ConnectionException('Connection timed out')]);

    expect(fn () => Daraja::stk()->push('0712345678', 100, 'INV-1'))
        ->toThrow(ApiRequestException::class, 'Could not reach the Safaricom Daraja API');
});

it('logs requests and responses with credentials redacted', function () {
    config()->set('laravel-daraja.logging.enabled', true);

    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('debug')
        ->atLeast()->once()
        ->withArgs(function (string $message, array $context): bool {
            if (str_contains($message, 'request') && array_key_exists('Password', $context)) {
                expect($context['Password'])->toBe('[redacted]');
            }

            return true;
        });

    fakeDaraja();

    Daraja::stk()->push('0712345678', 100, 'INV-1');
});

it('redacts the security credential on payout requests', function () {
    config()->set('laravel-daraja.logging.enabled', true);

    $logged = [];

    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('debug')->andReturnUsing(function (string $message, array $context) use (&$logged): void {
        $logged[] = $context;
    });

    fakeDaraja();

    Daraja::b2c()->business('0712345678', 100);

    $request = collect($logged)->firstWhere('CommandID', 'BusinessPayment');

    expect($request['SecurityCredential'])->toBe('[redacted]');
});

it('stays silent when logging is disabled', function () {
    config()->set('laravel-daraja.logging.enabled', false);

    Log::shouldReceive('channel')->never();

    fakeDaraja();

    expect(Daraja::stk()->push('0712345678', 100, 'INV-1')->accepted())->toBeTrue();
});

it('rejects an unknown mode', function () {
    Mode::fromConfig('staging');
})->throws(ConfigurationException::class, 'Unknown Daraja mode [staging]');

it('rejects a missing mode', function () {
    config()->set('laravel-daraja.mode', null);

    Daraja::accessToken();
})->throws(ConfigurationException::class, 'Unknown Daraja mode');

it('fails clearly when the certificate file cannot be read', function () {
    config()->set('laravel-daraja.certificate_path', '/nonexistent/path/cert.cer');

    app(SecurityCredential::class)->generate('password');
})->throws(ConfigurationException::class, 'developer.safaricom.co.ke');

it('fails clearly when the certificate cannot be parsed', function () {
    $path = sys_get_temp_dir().'/daraja-invalid-cert.cer';
    file_put_contents($path, 'this is not a certificate');

    config()->set('laravel-daraja.certificate_path', $path);

    try {
        app(SecurityCredential::class)->generate('password');
        $this->fail('Expected a ConfigurationException.');
    } catch (ConfigurationException $e) {
        // A PHP warning here would surface as an ErrorException instead, hiding
        // the guidance, so the message matters as much as the type.
        expect($e->getMessage())->toContain('could not be parsed');
    } finally {
        @unlink($path);
    }
});

it('returns null for an optional url that is not set', function () {
    expect(app(CallbackUrl::class)->resolveOptional(null))->toBeNull()
        ->and(app(CallbackUrl::class)->resolveOptional(''))->toBeNull()
        ->and(app(CallbackUrl::class)->resolveOptional('https://example.test/x'))
        ->toBe('https://example.test/x');
});

it('exposes the raw client for endpoints the package does not wrap', function () {
    fakeDaraja(['*/mpesa/custom/*' => Http::response(['ok' => true])]);

    expect(Daraja::client()->post('mpesa/custom/endpoint', ['A' => 1]))->toBe(['ok' => true]);
});

it('supports GET requests through the client', function () {
    fakeDaraja(['*/mpesa/lookup*' => Http::response(['found' => true])]);

    expect(Daraja::client()->get('mpesa/lookup', ['id' => 7]))->toBe(['found' => true]);

    Http::assertSent(fn ($request) => ! str_contains($request->url(), 'mpesa/lookup')
        || str_contains($request->url(), 'id=7'));
});
