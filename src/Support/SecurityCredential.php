<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Support;

use Illuminate\Contracts\Config\Repository;
use Starnerz\LaravelDaraja\Enums\Mode;
use Starnerz\LaravelDaraja\Exceptions\ConfigurationException;

/**
 * Encrypts the initiator password with Safaricom's public certificate.
 *
 * Safaricom publishes a different certificate per environment, so the correct
 * one is chosen from the configured mode unless an explicit path is given.
 */
final class SecurityCredential
{
    public function __construct(private readonly Repository $config) {}

    public function generate(?string $plaintext = null): string
    {
        $plaintext ??= $this->config->get('laravel-daraja.initiator.credential');

        if (blank($plaintext)) {
            throw ConfigurationException::missing('initiator.credential');
        }

        $path = $this->certificatePath();

        // certificatePath() has already established the file is readable, so
        // this cannot emit a warning.
        $certificate = (string) file_get_contents($path);

        // Parsing is checked separately: an unreadable key makes
        // openssl_public_encrypt() emit a warning, which Laravel promotes to an
        // ErrorException and which would mask the guidance below.
        $publicKey = @openssl_pkey_get_public($certificate);

        if ($publicKey === false) {
            throw new ConfigurationException(
                "The Safaricom certificate at [{$path}] could not be parsed: ".
                (openssl_error_string() ?: 'it is not a valid X.509 certificate').'.',
            );
        }

        if (! @openssl_public_encrypt((string) $plaintext, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING)) {
            throw new ConfigurationException(
                'Failed to encrypt the initiator credential: '.
                (openssl_error_string() ?: 'unknown OpenSSL error').'.',
            );
        }

        return base64_encode((string) $encrypted);
    }

    public function certificatePath(): string
    {
        $configured = $this->config->get('laravel-daraja.certificate_path');

        $path = filled($configured)
            ? (string) $configured
            : dirname(__DIR__, 2).'/certs/'.Mode::fromConfig($this->config->get('laravel-daraja.mode'))->certificate();

        // Checked for both the bundled and the configured path: a typo in
        // certificate_path should produce guidance, not a file_get_contents
        // warning promoted to an ErrorException.
        if (! is_readable($path)) {
            throw ConfigurationException::missingCertificate($path);
        }

        return $path;
    }
}
