<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Starnerz\LaravelDaraja\Events\DarajaRequestSending;
use Starnerz\LaravelDaraja\Events\DarajaResponseReceived;
use Starnerz\LaravelDaraja\Exceptions\ApiRequestException;
use Starnerz\LaravelDaraja\Facades\Daraja;

beforeEach(function () {
    cache()->flush();
});

it('announces every request before it leaves', function () {
    Event::fake([DarajaRequestSending::class]);
    fakeDaraja();

    Daraja::stk()->push('0712345678', 100, 'INV-001', 'Order 001');

    Event::assertDispatched(DarajaRequestSending::class, function (DarajaRequestSending $event): bool {
        if (! str_contains($event->endpoint, 'stkpush')) {
            return false;
        }

        expect($event->method)->toBe('POST')
            ->and($event->payload['BusinessShortCode'])->toBe('174379')
            ->and($event->payload['AccountReference'])->toBe('INV-001');

        return true;
    });
});

it('keeps secrets out of the request event', function () {
    Event::fake([DarajaRequestSending::class]);
    fakeDaraja();

    Daraja::stk()->push('0712345678', 100, 'INV-001', 'Order 001');

    Event::assertDispatched(DarajaRequestSending::class, function (DarajaRequestSending $event): bool {
        if (! str_contains($event->endpoint, 'stkpush')) {
            return false;
        }

        // The STK password is derived from the passkey, so it must never be
        // persisted by a listener recording this event.
        expect($event->payload['Password'])->toBe('[redacted]');

        return true;
    });
});

it('reports the response with its status and elapsed time', function () {
    Event::fake([DarajaResponseReceived::class]);
    fakeDaraja();

    Daraja::stk()->push('0712345678', 100, 'INV-001', 'Order 001');

    Event::assertDispatched(DarajaResponseReceived::class, function (DarajaResponseReceived $event): bool {
        if (! str_contains($event->endpoint, 'stkpush')) {
            return false;
        }

        expect($event->status)->toBe(200)
            ->and($event->successful())->toBeTrue()
            ->and($event->durationMs)->toBeFloat()->toBeGreaterThanOrEqual(0.0)
            ->and($event->body)->toBeArray()
            ->and($event->body['CheckoutRequestID'])->toBe('ws_CO_191220191020363925');

        return true;
    });
});

it('reports a rejected request before turning it into an exception', function () {
    Event::fake([DarajaResponseReceived::class]);
    fakeDaraja([
        '*/mpesa/stkpush/*' => Http::response(payload('error-bad-request'), 400),
    ]);

    expect(fn () => Daraja::stk()->push('0712345678', 100, 'INV-001', 'Order 001'))
        ->toThrow(ApiRequestException::class);

    Event::assertDispatched(DarajaResponseReceived::class, function (DarajaResponseReceived $event): bool {
        if (! str_contains($event->endpoint, 'stkpush')) {
            return false;
        }

        expect($event->status)->toBe(400)
            ->and($event->successful())->toBeFalse();

        return true;
    });
});

it('stays silent about a response that never arrived', function () {
    Event::fake([DarajaRequestSending::class, DarajaResponseReceived::class]);
    Http::fake([
        '*/oauth/*' => Http::response(payload('oauth-token')),
        '*/mpesa/stkpush/*' => fn () => throw new ConnectionException('Connection timed out'),
    ]);

    expect(fn () => Daraja::stk()->push('0712345678', 100, 'INV-001', 'Order 001'))
        ->toThrow(ApiRequestException::class);

    // The request is on record; the absence of a response event is how a
    // listener distinguishes an unanswered call from a rejected one.
    Event::assertDispatched(DarajaRequestSending::class);
    Event::assertNotDispatched(DarajaResponseReceived::class);
});
