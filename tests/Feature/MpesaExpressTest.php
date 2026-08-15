<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Starnerz\LaravelDaraja\Enums\TransactionType;
use Starnerz\LaravelDaraja\Exceptions\ApiRequestException;
use Starnerz\LaravelDaraja\Facades\Daraja;

beforeEach(function () {
    cache()->flush();
});

it('pushes a payment prompt and returns a typed response', function () {
    fakeDaraja();

    $response = Daraja::stk()->push(
        phone: '0712345678',
        amount: 100,
        accountReference: 'INV-001',
        description: 'Order 001',
    );

    expect($response->accepted())->toBeTrue()
        ->and($response->checkoutRequestId)->toBe('ws_CO_191220191020363925')
        ->and($response->merchantRequestId)->toBe('29115-34620561-1')
        ->and($response->customerMessage)->toContain('accepted for processing');
});

it('sends the payload Daraja expects', function () {
    fakeDaraja();

    Daraja::stk()->push('0712345678', 100, 'INV-001', 'Order 001');

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), 'stkpush')) {
            return false;
        }

        $body = $request->data();

        expect($body['BusinessShortCode'])->toBe('174379')
            ->and($body['TransactionType'])->toBe('CustomerPayBillOnline')
            ->and($body['Amount'])->toBe('100')
            ->and($body['PartyA'])->toBe('254712345678')
            ->and($body['PhoneNumber'])->toBe('254712345678')
            ->and($body['PartyB'])->toBe('174379')
            ->and($body['AccountReference'])->toBe('INV-001')
            ->and($body['TransactionDesc'])->toBe('Order 001')
            ->and($body['CallBackURL'])->toBe('https://example.test/daraja/stk');

        // Password is base64(shortcode + passkey + timestamp)
        expect(base64_decode($body['Password'], true))
            ->toBe('174379test-pass-key'.$body['Timestamp']);

        // Timestamp is YmdHis in East Africa Time
        expect($body['Timestamp'])->toMatch('/^\d{14}$/');

        return true;
    });
});

it('bears the access token', function () {
    fakeDaraja();

    Daraja::stk()->push('0712345678', 100, 'INV-001');

    Http::assertSent(fn ($request) => ! str_contains($request->url(), 'oauth')
        ? $request->hasHeader('Authorization', 'Bearer c9SQxWWhmdVRlyh0zh8gZDTkubVF')
        : true);
});

it('supports buy goods transactions', function () {
    fakeDaraja();

    Daraja::stk()->push('0712345678', 100, 'INV-001', type: TransactionType::BuyGoods);

    Http::assertSent(fn ($request) => ! str_contains($request->url(), 'stkpush')
        || $request->data()['TransactionType'] === 'CustomerBuyGoodsOnline');
});

it('truncates the description to the length Daraja allows', function () {
    fakeDaraja();

    Daraja::stk()->push('0712345678', 100, 'INV-001', 'A description far longer than Daraja permits');

    Http::assertSent(fn ($request) => ! str_contains($request->url(), 'stkpush')
        || strlen($request->data()['TransactionDesc']) <= 13);
});

it('falls back to the account reference when no description is given', function () {
    fakeDaraja();

    Daraja::stk()->push('0712345678', 100, 'INV-001');

    Http::assertSent(fn ($request) => ! str_contains($request->url(), 'stkpush')
        || $request->data()['TransactionDesc'] === 'INV-001');
});

it('rounds amounts to whole shillings', function () {
    fakeDaraja();

    Daraja::stk()->push('0712345678', 100.75, 'INV-001');

    Http::assertSent(fn ($request) => ! str_contains($request->url(), 'stkpush')
        || $request->data()['Amount'] === '101');
});

it('queries the status of a push', function () {
    fakeDaraja();

    $response = Daraja::stk()->query('ws_CO_191220191020363925');

    expect($response->paid())->toBeTrue()
        ->and($response->cancelledByUser())->toBeFalse()
        ->and($response->resultCode)->toBe('0');

    Http::assertSent(fn ($request) => ! str_contains($request->url(), 'stkpushquery')
        || $request->data()['CheckoutRequestID'] === 'ws_CO_191220191020363925');
});

it('reports a push the customer cancelled', function () {
    fakeDaraja([
        '*/mpesa/stkpushquery/*' => Http::response([
            'ResultCode' => '1032',
            'ResultDesc' => 'Request cancelled by user',
        ]),
    ]);

    $response = Daraja::stk()->query('ws_CO_191220191020363925');

    expect($response->paid())->toBeFalse()
        ->and($response->cancelledByUser())->toBeTrue();
});

it('turns a Daraja error into a typed exception', function () {
    fakeDaraja([
        '*/mpesa/stkpush/*' => Http::response(payload('error-bad-request'), 400),
    ]);

    try {
        Daraja::stk()->push('0712345678', 100, 'INV-001');
        $this->fail('Expected an ApiRequestException.');
    } catch (ApiRequestException $e) {
        expect($e->errorCode)->toBe('401.002.01')
            ->and($e->requestId)->toBe('11728-2929992-1')
            ->and($e->status)->toBe(400)
            ->and($e->getMessage())->toContain('Invalid Access Token');
    }
});
