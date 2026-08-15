<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Starnerz\LaravelDaraja\Facades\Daraja;

beforeEach(function () {
    cache()->flush();
    fakeDaraja();
});

/**
 * @return array<string, mixed>
 */
function sentBody(string $needle): array
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

it('sends a B2C payout with the fields v3 requires', function () {
    Daraja::b2c()->business('0712345678', 500, 'Refund', 'Order 12');

    $body = sentBody('b2c/v3/paymentrequest');

    expect($body['CommandID'])->toBe('BusinessPayment')
        ->and($body['InitiatorName'])->toBe('testapi')
        ->and($body['Amount'])->toBe('500')
        ->and($body['PartyA'])->toBe('600000')
        ->and($body['PartyB'])->toBe('254712345678')
        ->and($body['Remarks'])->toBe('Refund')
        ->and($body['Occassion'])->toBe('Order 12')
        // Required from v3 onwards; generated when the caller omits it.
        ->and($body['OriginatorConversationID'])->not->toBeEmpty()
        ->and($body['SecurityCredential'])->not->toBeEmpty();
});

it('generates a distinct OriginatorConversationID per payout', function () {
    $seen = [];

    foreach (range(1, 3) as $i) {
        Daraja::b2c()->business('0712345678', 100);
    }

    Http::assertSent(function ($request) use (&$seen): bool {
        if (str_contains($request->url(), 'b2c/v3')) {
            $seen[] = $request->data()['OriginatorConversationID'];
        }

        return true;
    });

    expect($seen)->toHaveCount(3)->and(array_unique($seen))->toHaveCount(3);
});

it('honours an explicit OriginatorConversationID', function () {
    Daraja::b2c()->business('0712345678', 100, originatorConversationId: 'my-own-id');

    expect(sentBody('b2c/v3')['OriginatorConversationID'])->toBe('my-own-id');
});

it('maps each B2C command', function (string $method, string $command) {
    Daraja::b2c()->{$method}('0712345678', 100);

    expect(sentBody('b2c/v3')['CommandID'])->toBe($command);
})->with([
    ['salary', 'SalaryPayment'],
    ['business', 'BusinessPayment'],
    ['promotion', 'PromotionPayment'],
]);

it('pays a Pochi la Biashara wallet on its own endpoint', function () {
    Daraja::b2c()->pochi('0712345678', 250, 'Pochi payout');

    $body = sentBody('b2pochi/v1/paymentrequest');

    expect($body['CommandID'])->toBe('BusinessPayToPochi')
        ->and($body['PartyB'])->toBe('254712345678')
        ->and($body['Amount'])->toBe('250');
});

it('sends a business pay bill with both identifier types set to 4', function () {
    Daraja::b2b()->payBill('123456', 900, 'ACC-1', 'Supplier');

    $body = sentBody('b2b/v1/paymentrequest');

    expect($body['CommandID'])->toBe('BusinessPayBill')
        // Safaricom accepts only 4 on both sides of this API.
        ->and($body['SenderIdentifierType'])->toBe('4')
        ->and($body['RecieverIdentifierType'])->toBe('4')
        ->and($body['PartyA'])->toBe('600000')
        ->and($body['PartyB'])->toBe('123456')
        ->and($body['AccountReference'])->toBe('ACC-1')
        ->and($body)->not->toHaveKey('Requester');
});

it('keeps identifier type 4 for buy goods too', function () {
    Daraja::b2b()->buyGoods('123456', 900);

    $body = sentBody('b2b/v1/paymentrequest');

    expect($body['CommandID'])->toBe('BusinessBuyGoods')
        ->and($body['SenderIdentifierType'])->toBe('4')
        ->and($body['RecieverIdentifierType'])->toBe('4');
});

it('includes the requester when paying on a customer behalf', function () {
    Daraja::b2b()->payBill('123456', 900, 'ACC-1', 'Supplier', requester: '0712345678');

    expect(sentBody('b2b/v1')['Requester'])->toBe('254712345678');
});

it('caps the B2B account reference at 13 characters', function () {
    Daraja::b2b()->payBill('123456', 900, 'A-REFERENCE-THAT-IS-FAR-TOO-LONG');

    expect(strlen(sentBody('b2b/v1')['AccountReference']))->toBeLessThanOrEqual(13);
});

it('tops up a B2C short code with BusinessPayToBulk', function () {
    Daraja::b2b()->accountTopUp('600979', 5000, 'TOPUP');

    $body = sentBody('b2b/v1/paymentrequest');

    expect($body['CommandID'])->toBe('BusinessPayToBulk')
        ->and($body['PartyB'])->toBe('600979')
        ->and($body['Amount'])->toBe('5000');
});
