<?php

declare(strict_types=1);

use Starnerz\LaravelDaraja\Support\ResultParameters;

it('reads a normal list of pairs', function () {
    $params = ResultParameters::make([
        ['Key' => 'Amount', 'Value' => '190.00'],
        ['Key' => 'TransCompletedTime', 'Value' => '20221110110717'],
    ]);

    expect($params->string('Amount'))->toBe('190.00')
        ->and($params->float('Amount'))->toBe(190.0)
        ->and($params->has('TransCompletedTime'))->toBeTrue();
});

it('accepts a bare object where a list is expected', function () {
    // Safaricom degrades ResultParameter to a single object on failure.
    $params = ResultParameters::make(['Key' => 'BOCompletedTime', 'Value' => 20200120164825]);

    expect($params->string('BOCompletedTime'))->toBe('20200120164825')
        ->and($params->isEmpty())->toBeFalse();
});

it('keeps every value when a key repeats', function () {
    // Transaction Status returns DebitPartyName twice, once per side.
    $params = ResultParameters::make([
        ['Key' => 'DebitPartyName', 'Value' => '600310 - Safaricom333'],
        ['Key' => 'DebitPartyName', 'Value' => '254708374149 - John Doe'],
    ]);

    expect($params->all('DebitPartyName'))->toHaveCount(2)
        ->and($params->all('DebitPartyName')[1])->toBe('254708374149 - John Doe')
        // get() returns the first, which is why all() exists.
        ->and($params->string('DebitPartyName'))->toBe('600310 - Safaricom333');
});

it('survives a pair with no value at all', function () {
    // Real Transaction Status payloads contain {"Key": "TransactionReason"}.
    $params = ResultParameters::make([
        ['Key' => 'TransactionReason'],
        ['Key' => 'ReasonType', 'Value' => 'Business Payment to Customer via API'],
    ]);

    expect($params->has('TransactionReason'))->toBeTrue()
        ->and($params->get('TransactionReason'))->toBeNull()
        ->and($params->string('TransactionReason'))->toBe('')
        ->and($params->string('ReasonType'))->toContain('Business Payment');
});

it('reads the Name/Value casing used by STK callbacks', function () {
    $params = ResultParameters::make([
        ['Name' => 'MpesaReceiptNumber', 'Value' => 'NLJ7RT61SV'],
        ['Name' => 'Amount', 'Value' => 1.0],
    ]);

    expect($params->string('MpesaReceiptNumber'))->toBe('NLJ7RT61SV')
        ->and($params->float('Amount'))->toBe(1.0);
});

it('reads the lowercase casing used by M-Pesa Ratiba', function () {
    $params = ResultParameters::make([
        ['name' => 'status', 'value' => 'ACTIVE'],
        ['name' => 'TransactionID', 'value' => '2571168'],
    ]);

    expect($params->string('status'))->toBe('ACTIVE')
        ->and($params->string('TransactionID'))->toBe('2571168');
});

it('treats null and non-arrays as empty', function (mixed $input) {
    expect(ResultParameters::make($input)->isEmpty())->toBeTrue();
})->with([[null], ['a string'], [42], [[]], [[null]]]);

it('collapses repeats when flattened to a map', function () {
    $params = ResultParameters::make([
        ['Key' => 'DebitPartyName', 'Value' => 'first'],
        ['Key' => 'DebitPartyName', 'Value' => 'second'],
        ['Key' => 'Amount', 'Value' => '10'],
    ]);

    expect($params->toArray())->toBe([
        'DebitPartyName' => 'first',
        'Amount' => '10',
    ]);
});
