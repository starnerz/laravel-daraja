<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Starnerz\LaravelDaraja\Enums\QrTransactionType;
use Starnerz\LaravelDaraja\Enums\StandingOrderFrequency;
use Starnerz\LaravelDaraja\Exceptions\ConfigurationException;
use Starnerz\LaravelDaraja\Exceptions\DarajaException;
use Starnerz\LaravelDaraja\Facades\Daraja;

beforeEach(function () {
    cache()->flush();
    fakeDaraja();
});

it('generates a dynamic QR code and decodes it', function () {
    $response = Daraja::qr()->generate('TEST SUPERMARKET', 'INV-9', 100, QrTransactionType::BuyGoods);

    expect($response->qrCode)->not->toBeEmpty()
        // The API returns base64 PNG data.
        ->and($response->png())->toStartWith("\x89PNG")
        ->and($response->dataUri())->toStartWith('data:image/png;base64,');

    $body = sentBody('qrcode/v1/generate');

    expect($body['MerchantName'])->toBe('TEST SUPERMARKET')
        ->and($body['RefNo'])->toBe('INV-9')
        ->and($body['TrxCode'])->toBe('BG')
        ->and($body['Size'])->toBe('300');
});

it('supports every QR transaction type', function (QrTransactionType $type, string $code) {
    Daraja::qr()->generate('Shop', 'REF', 10, $type);

    expect(sentBody('qrcode/v1')['TrxCode'])->toBe($code);
})->with([
    [QrTransactionType::BuyGoods, 'BG'],
    [QrTransactionType::WithdrawAtAgent, 'WA'],
    [QrTransactionType::PayBill, 'PB'],
    [QrTransactionType::SendMoney, 'SM'],
    [QrTransactionType::SendToBusiness, 'SB'],
]);

it('creates a standing order', function () {
    $response = Daraja::standingOrder()->create(
        name: 'Gym membership',
        phone: '0712345678',
        amount: 1500,
        startDate: '20260901',
        endDate: '20270901',
        accountReference: 'MEMBER-1',
        frequency: StandingOrderFrequency::Monthly,
    );

    // Ratiba answers 200 rather than the 0 used elsewhere in Daraja.
    expect($response->accepted())->toBeTrue()
        ->and($response->responseCode)->toBe('200');

    $body = sentBody('createStandingOrderExternal');

    expect($body['StandingOrderName'])->toBe('Gym membership')
        ->and($body['PartyA'])->toBe('254712345678')
        ->and($body['Frequency'])->toBe('5')
        ->and($body['TransactionType'])->toBe('Standing Order Pay Bill External Third Party')
        ->and($body['CustomStoId'])->not->toBeEmpty();
});

it('switches the standing order transaction type for a till', function () {
    config()->set('laravel-daraja.initiator.type', 'till');

    Daraja::standingOrder()->create('Rent', '0712345678', 100, '20260901', '20270901', 'REF');

    expect(sentBody('createStandingOrder')['TransactionType'])
        ->toBe('Standing Order Pay Merchant External Third Party');
});

it('formats standing order dates from a DateTime', function () {
    Daraja::standingOrder()->create(
        'Savings', '0712345678', 100,
        new DateTimeImmutable('2026-09-01'),
        new DateTimeImmutable('2027-09-01'),
        'REF',
    );

    $body = sentBody('createStandingOrder');

    expect($body['StartDate'])->toBe('20260901')->and($body['EndDate'])->toBe('20270901');
});

it('pushes a B2B express checkout to a till operator', function () {
    $response = Daraja::b2bExpress()->push('000001', '000002', 100, 'INV-3');

    expect($response->initiated())->toBeTrue()
        ->and($response->status)->toContain('USSD Initiated');

    $body = sentBody('ussdpush/get-msisdn');

    expect($body['primaryShortCode'])->toBe('000001')
        ->and($body['receiverShortCode'])->toBe('000002')
        ->and($body['paymentRef'])->toBe('INV-3')
        // Falls back to the configured partner name.
        ->and($body['partnerName'])->toBe('Test Vendor')
        ->and($body['RequestRefID'])->not->toBeEmpty();
});

it('opts in to bill manager and returns the app key', function () {
    $response = Daraja::billManager()->optIn('billing@example.test', '0710000000');

    expect($response->successful())->toBeTrue()
        // Returned once and never again, so it must be surfaced.
        ->and($response->appKey)->toBe('AG_2376487236_126732989KJ');

    expect(sentBody('billmanager-invoice/optin')['sendReminders'])->toBe('1');
});

it('sends a single invoice with its line items', function () {
    Daraja::billManager()->invoice(
        externalReference: 'INV-100',
        billedFullName: 'John Doe',
        billedPhoneNumber: '0712345678',
        billedPeriod: 'August 2026',
        invoiceName: 'Water',
        dueDate: '2026-09-15',
        accountReference: 'ACC-1',
        amount: 800,
        items: [
            ['itemName' => 'food', 'amount' => 700],
            ['itemName' => 'water', 'amount' => 100],
        ],
    );

    $body = sentBody('single-invoicing');

    expect($body['billedPhoneNumber'])->toBe('254712345678')
        ->and($body['invoiceItems'])->toHaveCount(2)
        ->and($body['invoiceItems'][0]['itemName'])->toBe('food');
});

it('rejects an empty bulk invoice batch', function () {
    Daraja::billManager()->bulkInvoice([]);
})->throws(DarajaException::class, 'At least one invoice');

it('rejects a bulk batch over the documented limit', function () {
    Daraja::billManager()->bulkInvoice(array_fill(0, 1001, ['externalReference' => 'x']));
})->throws(DarajaException::class, 'maximum of 1000');

it('sends the app key as a header on bulk invoicing', function () {
    Daraja::billManager()->bulkInvoice([['externalReference' => 'INV-1']]);

    Http::assertSent(fn ($request) => ! str_contains($request->url(), 'bulk-invoicing')
        || $request->hasHeader('appKey', 'test-app-key'));
});

it('fails clearly when no bill manager app key is configured', function () {
    config()->set('laravel-daraja.bill_manager.app_key', null);

    Daraja::billManager()->bulkInvoice([['externalReference' => 'INV-1']]);
})->throws(ConfigurationException::class, 'bill_manager.app_key');

it('cancels invoices singly and in bulk', function () {
    Daraja::billManager()->cancelInvoice('INV-1');
    expect(sentBody('cancel-single-invoice')['externalReference'])->toBe('INV-1');

    Daraja::billManager()->cancelInvoices(['INV-2', 'INV-3']);
    Http::assertSent(function ($request): bool {
        if (! str_contains($request->url(), 'cancel-bulk-invoices')) {
            return false;
        }

        expect($request->data())->toHaveCount(2)
            ->and($request->data()[1]['externalReference'])->toBe('INV-3');

        return true;
    });
});

it('registers a short code for pulling transactions', function () {
    Daraja::pull()->register();

    $body = sentBody('pulltransactions/v1/register');

    expect($body['RequestType'])->toBe('Pull')
        ->and($body['NominatedNumber'])->toBe('254722000000');
});

it('flattens the doubly nested pull transaction list', function () {
    $transactions = Daraja::pull()->query('2026-08-01 00:00:00', '2026-08-02 00:00:00');

    // "Response" arrives as an array of arrays.
    expect($transactions)->toHaveCount(2)
        ->and($transactions->first()->transactionId)->toBe('yzlyrEsRG1')
        ->and($transactions->first()->amount)->toBe('168.00')
        ->and($transactions->last()->billReference)->toBe('37207636393');
});

it('formats pull dates from a DateTime', function () {
    Daraja::pull()->query(new DateTimeImmutable('2026-08-01 08:36:00'), new DateTimeImmutable('2026-08-02 10:10:00'));

    expect(sentBody('pulltransactions/v1/query')['StartDate'])->toBe('2026-08-01 08:36:00');
});

it('calculates bonga points', function () {
    $response = Daraja::bonga()->calculatePoints(40);

    expect($response->successful())->toBeTrue()
        ->and($response->amount())->toBe('8')
        ->and($response->rate())->toBe('0.2');

    expect(sentBody('calculate-points')['points'])->toBe('40');
});

it('redeems bonga points', function () {
    Daraja::bonga()->redeem('0712345678', 50, 20, 'ACC-7');

    $body = sentBody('redeem-paybill');

    expect($body['msisdn'])->toBe('254712345678')
        ->and($body['bongaPoints'])->toBe(20)
        ->and($body['conversionRate'])->toBe(0.2)
        ->and($body['accountNumber'])->toBe('ACC-7');
});
