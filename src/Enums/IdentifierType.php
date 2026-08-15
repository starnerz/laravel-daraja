<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Enums;

/**
 * Safaricom's organisation identifier types.
 */
enum IdentifierType: int
{
    case Msisdn = 1;
    case Till = 2;
    case ShortCode = 4;

    /**
     * Map the friendly names used in configuration onto the API values.
     */
    public static function fromConfig(?string $type): self
    {
        return match (strtolower((string) $type)) {
            'msisdn' => self::Msisdn,
            'till', 'tillnumber', 'buygoods' => self::Till,
            default => self::ShortCode,
        };
    }
}
