<?php

declare(strict_types=1);

use Starnerz\LaravelDaraja\Exceptions\DarajaException;
use Starnerz\LaravelDaraja\Support\PhoneNumber;

it('normalises every common Kenyan format', function (string $input, string $expected) {
    expect(PhoneNumber::normalise($input))->toBe($expected);
})->with([
    ['0712345678', '254712345678'],
    ['0112345678', '254112345678'],
    ['712345678', '254712345678'],
    ['112345678', '254112345678'],
    ['254712345678', '254712345678'],
    ['+254712345678', '254712345678'],
    ['+254 712 345 678', '254712345678'],
    ['0712-345-678', '254712345678'],
]);

it('rejects numbers that are not Safaricom mobile numbers', function (string $input) {
    PhoneNumber::normalise($input);
})->with([
    '25471234567',    // too short
    '2547123456789',  // too long
    '254812345678',   // not a 7 or 1 prefix
    'not a number',
    '',
])->throws(DarajaException::class);
