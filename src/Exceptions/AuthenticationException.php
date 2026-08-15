<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Exceptions;

use Throwable;

class AuthenticationException extends DarajaException
{
    public static function tokenRequestFailed(string $reason, int $status = 0, ?Throwable $previous = null): self
    {
        return new self(
            "Could not obtain a Daraja access token: {$reason}",
            $status,
            $previous,
        );
    }

    public static function malformedTokenResponse(): self
    {
        return new self(
            'The Daraja authorization endpoint did not return an access_token. '.
            'Check that your consumer key and secret belong to the environment set in [laravel-daraja.mode].',
        );
    }
}
