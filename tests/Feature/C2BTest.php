<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Starnerz\LaravelDaraja\Exceptions\ConfigurationException;
use Starnerz\LaravelDaraja\Exceptions\DarajaException;
use Starnerz\LaravelDaraja\Facades\Daraja;

beforeEach(function () {
    cache()->flush();

    config()->set('laravel-daraja.urls.c2b.confirmation', 'https://example.test/daraja/confirm');
    config()->set('laravel-daraja.urls.c2b.validation', 'https://example.test/daraja/validate');

    fakeDaraja();
});

it('registers the configured callback urls', function () {
    $result = Daraja::c2b()->registerUrls();

    expect($result->accepted())->toBeTrue()
        // Safaricom misspells this key on the register endpoint.
        ->and($result->originatorConversationId)->toBe('7619-37765134-1');

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), 'registerurl')) {
            return false;
        }

        expect($request->data())->toMatchArray([
            'ShortCode' => '600000',
            'ResponseType' => 'Completed',
            'ConfirmationURL' => 'https://example.test/daraja/confirm',
            'ValidationURL' => 'https://example.test/daraja/validate',
        ]);

        return true;
    });
});

it('resolves a route name into an absolute url', function () {
    Route::get('/callbacks/confirm', fn () => 'ok')->name('daraja.confirm');

    config()->set('laravel-daraja.urls.c2b.confirmation', 'daraja.confirm');

    Daraja::c2b()->registerUrls();

    Http::assertSent(fn ($request) => ! str_contains($request->url(), 'registerurl')
        || $request->data()['ConfirmationURL'] === route('daraja.confirm'));
});

it('names the missing configuration key when a url is not set', function () {
    config()->set('laravel-daraja.urls.c2b.validation', null);

    Daraja::c2b()->registerUrls();
})->throws(ConfigurationException::class, 'laravel-daraja.urls.c2b.validation');

it('simulates a pay bill payment in the sandbox', function () {
    $result = Daraja::c2b()->simulatePayBill('0712345678', 500, 'INV-77');

    expect($result->accepted())->toBeTrue();

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), 'simulate')) {
            return false;
        }

        expect($request->data())->toMatchArray([
            'ShortCode' => '600000',
            'CommandID' => 'CustomerPayBillOnline',
            'Amount' => '500',
            'Msisdn' => '254712345678',
            'BillRefNumber' => 'INV-77',
        ]);

        return true;
    });
});

it('simulates a buy goods payment in the sandbox', function () {
    Daraja::c2b()->simulateBuyGoods('0712345678', 500);

    Http::assertSent(fn ($request) => ! str_contains($request->url(), 'simulate')
        || $request->data()['CommandID'] === 'CustomerBuyGoodsOnline');
});

it('refuses to simulate against the live environment', function () {
    config()->set('laravel-daraja.mode', 'live');

    Daraja::c2b()->simulatePayBill('0712345678', 500, 'INV-77');
})->throws(DarajaException::class, 'only available in the sandbox');
