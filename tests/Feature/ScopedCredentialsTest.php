<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Starnerz\LaravelDaraja\Events\DarajaRequestSending;
use Starnerz\LaravelDaraja\Events\DarajaResponseReceived;
use Starnerz\LaravelDaraja\Facades\Daraja;

beforeEach(function () {
    cache()->flush();
});

/**
 * One merchant's settings, as a platform would load them from its own records.
 *
 * @return array<string, mixed>
 */
function scopedMerchant(): array
{
    return [
        'consumer_key' => 'merchant-key',
        'consumer_secret' => 'merchant-secret',
        'stk.short_code' => '4012345',
        'stk.pass_key' => 'merchant-pass-key',
    ];
}

it('sends a call with the settings it was given', function () {
    fakeDaraja();

    Daraja::using(scopedMerchant())->stk()->push('0712345678', 100, 'INV-001', 'Order 001');

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/oauth/')
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('merchant-key:merchant-secret')));

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'stkpush')
        && $request['BusinessShortCode'] === '4012345');
});

it('leaves application configuration untouched', function () {
    fakeDaraja();

    Daraja::using(scopedMerchant())->stk()->push('0712345678', 100, 'INV-001', 'Order 001');

    expect(config('laravel-daraja.consumer_key'))->toBe('test-consumer-key')
        ->and(config('laravel-daraja.stk.short_code'))->toBe('174379')
        ->and(config('laravel-daraja.connection'))->toBeNull();
});

it('leaves the next unscoped call on the configured merchant', function () {
    fakeDaraja();

    Daraja::using(scopedMerchant())->stk()->push('0712345678', 100, 'INV-001', 'Order 001');
    Daraja::stk()->push('0712345678', 100, 'INV-002', 'Order 002');

    $shortcodes = Http::recorded()
        ->map(fn (array $pair): mixed => $pair[0])
        ->filter(fn (Request $request): bool => str_contains($request->url(), 'stkpush'))
        ->map(fn (Request $request): mixed => $request['BusinessShortCode'])
        ->values()
        ->all();

    expect($shortcodes)->toBe(['4012345', '174379']);
});

it('falls back to configuration for anything not given', function () {
    fakeDaraja();

    Daraja::using(['stk.pass_key' => 'merchant-pass-key'])->stk()->push('0712345678', 100, 'INV-001', 'Order 001');

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'stkpush')
        && $request['BusinessShortCode'] === '174379');
});

it('accepts nested settings as well as dot notation', function () {
    fakeDaraja();

    Daraja::using(['stk' => ['short_code' => '4012345']])->stk()->push('0712345678', 100, 'INV-001', 'Order 001');

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'stkpush')
        && $request['BusinessShortCode'] === '4012345');
});

it('keeps each merchant on its own access token', function () {
    fakeDaraja();

    $first = Daraja::using(['consumer_key' => 'merchant-a'])->accessToken();
    $second = Daraja::using(['consumer_key' => 'merchant-b'])->accessToken();
    Daraja::using(['consumer_key' => 'merchant-a'])->accessToken();

    // Two merchants, two token requests; the third call reuses merchant A's.
    expect($first)->toBe($second);
    Http::assertSentCount(2);
});

it('reaches the host for the mode it was given', function () {
    fakeDaraja();

    Daraja::using(['mode' => 'live'])->stk()->push('0712345678', 100, 'INV-001', 'Order 001');

    Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://api.safaricom.co.ke/mpesa/stkpush'));
});

it('names the connection on both client events', function () {
    Event::fake([DarajaRequestSending::class, DarajaResponseReceived::class]);
    fakeDaraja();

    Daraja::using(scopedMerchant(), connection: 'tenband')->stk()->push('0712345678', 100, 'INV-001', 'Order 001');

    Event::assertDispatched(DarajaRequestSending::class, fn (DarajaRequestSending $event): bool => $event->connection === 'tenband');
    Event::assertDispatched(DarajaResponseReceived::class, fn (DarajaResponseReceived $event): bool => $event->connection === 'tenband');
});

it('names no connection on an unscoped call', function () {
    Event::fake([DarajaRequestSending::class]);
    fakeDaraja();

    Daraja::stk()->push('0712345678', 100, 'INV-001', 'Order 001');

    Event::assertDispatched(DarajaRequestSending::class, fn (DarajaRequestSending $event): bool => $event->connection === null);
    expect(Daraja::connection())->toBeNull();
});

it('scopes a scoped instance further without losing what it had', function () {
    fakeDaraja();

    $scoped = Daraja::using(scopedMerchant(), connection: 'tenband')->using(['stk.short_code' => '4099999']);
    $scoped->stk()->push('0712345678', 100, 'INV-001', 'Order 001');

    expect($scoped->connection())->toBe('tenband');

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/oauth/')
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('merchant-key:merchant-secret')));
    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'stkpush')
        && $request['BusinessShortCode'] === '4099999');
});
