<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Support;

use Starnerz\LaravelDaraja\Exceptions\DarajaException;

/**
 * Normalises Kenyan mobile numbers into the 2547XXXXXXXX / 2541XXXXXXXX form
 * that every Daraja endpoint expects.
 */
final class PhoneNumber
{
    public static function normalise(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        $digits = match (true) {
            // 0712345678 -> 254712345678
            str_starts_with($digits, '0') => '254'.substr($digits, 1),
            // 712345678 -> 254712345678
            strlen($digits) === 9 && (str_starts_with($digits, '7') || str_starts_with($digits, '1')) => '254'.$digits,
            default => $digits,
        };

        if (! preg_match('/^254[71]\d{8}$/', $digits)) {
            throw new DarajaException(
                "[{$number}] is not a valid Safaricom mobile number. Expected a number in the form 2547XXXXXXXX.",
            );
        }

        return $digits;
    }
}
