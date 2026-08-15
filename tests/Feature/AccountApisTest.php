<?php

declare(strict_types=1);

use Starnerz\LaravelDaraja\Exceptions\DarajaException;
use Starnerz\LaravelDaraja\Facades\Daraja;

beforeEach(function () {
    cache()->flush();
    fakeDaraja();
});

it('queries the account balance', function () {
    $result = Daraja::balance()->query('End of day check');

    expect($result->accepted())->toBeTrue();

    $body = sentBody('accountbalance/v1/query');

    expect($body['CommandID'])->toBe('AccountBalance')
        ->and($body['Initiator'])->toBe('testapi')
        ->and($body['PartyA'])->toBe('600000')
        ->and($body['IdentifierType'])->toBe('4')
        ->and($body['Remarks'])->toBe('End of day check')
        // This API has no Occasion field at all.
        ->and($body)->not->toHaveKey('Occasion')
        ->and($body)->not->toHaveKey('Occassion');
});

it('queries a transaction by receipt number', function () {
    Daraja::transaction()->query('NEF61H8J60', 'Reconciliation', 'Batch 4');

    $body = sentBody('transactionstatus/v1/query');

    expect($body['CommandID'])->toBe('TransactionStatusQuery')
        ->and($body['TransactionID'])->toBe('NEF61H8J60')
        // This API spells it with one "s", unlike B2C.
        ->and($body['Occasion'])->toBe('Batch 4')
        ->and($body)->not->toHaveKey('Occassion')
        ->and($body)->not->toHaveKey('OriginalConversationID');
});

it('queries a transaction by originator conversation id', function () {
    Daraja::transaction()->queryByConversationId('7071-4170-a0e5-8345632bad442144258');

    $body = sentBody('transactionstatus/v1/query');

    expect($body['OriginalConversationID'])->toBe('7071-4170-a0e5-8345632bad442144258')
        ->and($body)->not->toHaveKey('TransactionID');
});

it('refuses to query a transaction with neither identifier', function () {
    Daraja::transaction()->query('');
})->throws(DarajaException::class, 'either an M-Pesa receipt number or an OriginalConversationID');

it('reverses a transaction with the fixed identifier type', function () {
    $result = Daraja::reversal()->reverse('PDU91HIVIT', 200, 'Duplicate payment');

    expect($result->accepted())->toBeTrue();

    $body = sentBody('reversal/v1/request');

    expect($body['CommandID'])->toBe('TransactionReversal')
        ->and($body['TransactionID'])->toBe('PDU91HIVIT')
        ->and($body['Amount'])->toBe('200')
        ->and($body['ReceiverParty'])->toBe('600000')
        // Safaricom requires the literal 11 here, not the configured type.
        ->and($body['RecieverIdentifierType'])->toBe('11')
        // Reversal has no Occassion field.
        ->and($body)->not->toHaveKey('Occassion');
});

it('keeps the reversal identifier at 11 whatever the configured type', function () {
    config()->set('laravel-daraja.initiator.type', 'till');

    Daraja::reversal()->reverse('PDU91HIVIT', 200);

    expect(sentBody('reversal/v1')['RecieverIdentifierType'])->toBe('11');
});
