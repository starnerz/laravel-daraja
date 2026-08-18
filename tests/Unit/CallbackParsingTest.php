<?php

declare(strict_types=1);

use Starnerz\LaravelDaraja\Data\Callbacks\C2BTransaction;
use Starnerz\LaravelDaraja\Data\Callbacks\ResultCallback;
use Starnerz\LaravelDaraja\Data\Callbacks\StkCallback;

it('parses a successful STK callback', function () {
    $callback = StkCallback::fromArray(payload('stk-callback-success'));

    expect($callback->successful())->toBeTrue()
        ->and($callback->checkoutRequestId)->toBe('ws_CO_191220191020363925')
        ->and($callback->amount())->toBe(1.0)
        ->and($callback->receipt())->toBe('NLJ7RT61SV')
        ->and($callback->phoneNumber())->toBe('254708374149')
        ->and($callback->transactionDate())->toBe('20191219102115');
});

it('tolerates a metadata item that carries no Value', function () {
    // Safaricom sends {"Name": "Balance"} with the key omitted entirely on a
    // successful push. Observed in sandbox, not documented.
    $callback = StkCallback::fromArray(payload('stk-callback-success'));

    expect($callback->metadata->get('Balance'))->toBeNull()
        ->and($callback->metadata->string('Balance'))->toBe('')
        // The items after it must still parse.
        ->and($callback->receipt())->toBe('NLJ7RT61SV');
});

it('parses a cancelled STK callback that carries no metadata', function () {
    $callback = StkCallback::fromArray(payload('stk-callback-cancelled'));

    expect($callback->successful())->toBeFalse()
        ->and($callback->cancelledByUser())->toBeTrue()
        ->and($callback->metadata->isEmpty())->toBeTrue()
        // Accessors must not blow up when CallbackMetadata is absent.
        ->and($callback->amount())->toBe(0.0)
        ->and($callback->receipt())->toBe('');
});

it('treats the numeric callback ResultCode as a string', function () {
    // The callback sends 0 as a number, the query response sends "0".
    expect(StkCallback::fromArray(payload('stk-callback-success'))->resultCode)->toBe('0');
});

it('parses a successful B2C result', function () {
    $result = ResultCallback::fromArray(payload('b2c-result-success'));

    expect($result->successful())->toBeTrue()
        ->and($result->transactionId)->toBe('SG632NMUAB')
        ->and($result->amount())->toBe(10.0)
        ->and($result->receipt())->toBe('SG632NMUAB')
        ->and($result->receiverName())->toContain('NICHOLAS JOHN SONGOK')
        ->and($result->recipientIsRegistered())->toBeTrue()
        ->and($result->hasRealTransactionId())->toBeTrue();
});

it('parses a failed B2C result that has no ResultParameters', function () {
    $result = ResultCallback::fromArray(payload('b2c-result-failure'));

    expect($result->successful())->toBeFalse()
        ->and($result->resultCode)->toBe('2001')
        ->and($result->resultDescription)->toContain('initiator information is invalid')
        ->and($result->parameters->isEmpty())->toBeTrue()
        ->and($result->amount())->toBe(0.0)
        ->and($result->recipientIsRegistered())->toBeNull();
});

it('reads ReferenceItem when it arrives as a bare object', function () {
    $result = ResultCallback::fromArray(payload('b2c-result-failure'));

    expect($result->reference->string('QueueTimeoutURL'))->toContain('b2cresults');
});

it('recognises a placeholder transaction id', function () {
    $result = ResultCallback::fromArray([
        'Result' => ['ResultCode' => 2001, 'TransactionID' => 'OAK0000000'],
    ]);

    expect($result->hasRealTransactionId())->toBeFalse();
});

it('reads balances out of an Account Balance result', function () {
    $result = ResultCallback::fromArray([
        'Result' => [
            'ResultCode' => 0,
            'ResultParameters' => [
                'ResultParameter' => [
                    [
                        'Key' => 'AccountBalance',
                        'Value' => 'Working Account|KES|700000.00|700000.00|0.00|0.00'
                            .'&Utility Account|KES|228037.00|228037.00|0.00|0.00',
                    ],
                ],
            ],
        ],
    ]);

    expect($result->balances()->utility()['balance'])->toBe(228037.0)
        ->and($result->balances()->working()['available'])->toBe(700000.0);
});

it('keeps both party names on a transaction status result', function () {
    $result = ResultCallback::fromArray([
        'Result' => [
            'ResultCode' => 0,
            'ResultParameters' => [
                'ResultParameter' => [
                    ['Key' => 'DebitPartyName', 'Value' => '600310 - Safaricom333'],
                    ['Key' => 'DebitPartyName', 'Value' => '254708374149 - John Doe'],
                ],
            ],
        ],
    ]);

    expect($result->partyNames())->toHaveCount(2);
});

it('parses a C2B confirmation', function () {
    $transaction = C2BTransaction::fromArray(payload('c2b-confirmation'));

    expect($transaction->transactionId)->toBe('RKL51ZDR4F')
        ->and($transaction->amount)->toBe('5.00')
        ->and($transaction->billReferenceNumber)->toBe('Sample Transaction')
        ->and($transaction->msisdn)->toBe('2547 ***** 126')
        ->and($transaction->fullName())->toBe('NICHOLAS')
        ->and($transaction->isPayBill())->toBeTrue()
        ->and($transaction->isConfirmation())->toBeTrue();
});

it('distinguishes a validation request from a confirmation', function () {
    $validation = C2BTransaction::fromArray(
        ['OrgAccountBalance' => ''] + payload('c2b-confirmation'),
    );

    expect($validation->isConfirmation())->toBeFalse();
});
