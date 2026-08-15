<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Exceptions;

class ConfigurationException extends DarajaException
{
    public static function missing(string $key): self
    {
        return new self(
            "The [laravel-daraja.{$key}] configuration value is required but has not been set.",
        );
    }

    public static function invalidMode(string $mode): self
    {
        return new self(
            "Unknown Daraja mode [{$mode}]. Supported modes are \"sandbox\" and \"live\".",
        );
    }

    public static function missingCertificate(string $path): self
    {
        return new self(
            "The Safaricom public certificate could not be read at [{$path}]. ".
            'Download the current certificate from https://developer.safaricom.co.ke and '.
            'point [laravel-daraja.certificate_path] at it.',
        );
    }
}
