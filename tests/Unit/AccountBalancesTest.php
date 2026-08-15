<?php

declare(strict_types=1);

use Starnerz\LaravelDaraja\Support\AccountBalances;

// Verbatim from the Account Balance documentation.
const PACKED = 'Working Account|KES|700000.00|700000.00|0.00|0.00'
    .'&Float Account|KES|0.00|0.00|0.00|0.00'
    .'&Utility Account|KES|228037.00|228037.00|0.00|0.00'
    .'&Charges Paid Account|KES|-1540.00|-1540.00|0.00|0.00'
    .'&Organization Settlement Account|KES|0.00|0.00|0.00|0.00';

it('splits every account out of the packed string', function () {
    $balances = AccountBalances::parse(PACKED);

    expect($balances->names())->toBe([
        'Working Account',
        'Float Account',
        'Utility Account',
        'Charges Paid Account',
        'Organization Settlement Account',
    ]);
});

it('exposes the accounts that matter by name', function () {
    $balances = AccountBalances::parse(PACKED);

    expect($balances->utility()['balance'])->toBe(228037.0)
        ->and($balances->working()['balance'])->toBe(700000.0)
        // The charges account always carries a negative balance.
        ->and($balances->chargesPaid()['balance'])->toBe(-1540.0);
});

it('parses each field of an account', function () {
    $account = AccountBalances::parse(PACKED)->account('Utility Account');

    expect($account)->toBe([
        'name' => 'Utility Account',
        'currency' => 'KES',
        'balance' => 228037.0,
        'available' => 228037.0,
        'reserved' => 0.0,
        'uncleared' => 0.0,
    ]);
});

it('returns null for an account that is not present', function () {
    expect(AccountBalances::parse(PACKED)->account('Nonexistent Account'))->toBeNull()
        ->and(AccountBalances::parse(PACKED)->balanceOf('Nonexistent Account'))->toBe(0.0);
});

it('handles an empty or missing balance string', function (?string $input) {
    expect(AccountBalances::parse($input)->names())->toBe([]);
})->with([null, '', '   ']);

it('tolerates an account with missing trailing fields', function () {
    $balances = AccountBalances::parse('Utility Account|KES|500.00');

    expect($balances->utility())->toBe([
        'name' => 'Utility Account',
        'currency' => 'KES',
        'balance' => 500.0,
        'available' => 0.0,
        'reserved' => 0.0,
        'uncleared' => 0.0,
    ]);
});
