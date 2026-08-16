<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Enums;

use Starnerz\LaravelDaraja\Exceptions\ConfigurationException;

enum Mode: string
{
    case Sandbox = 'sandbox';
    case Live = 'live';

    public static function fromConfig(?string $mode): self
    {
        return self::tryFrom((string) $mode)
            ?? throw ConfigurationException::invalidMode((string) $mode);
    }

    public function isLive(): bool
    {
        return $this === self::Live;
    }

    /**
     * The certificate shipped with the package for this environment.
     *
     * Filenames match the downloads on the Daraja portal so their provenance
     * stays obvious.
     */
    public function certificate(): string
    {
        return $this === self::Live
            ? 'ProductionCertificate.cer'
            : 'SandboxCertificate.cer';
    }
}
