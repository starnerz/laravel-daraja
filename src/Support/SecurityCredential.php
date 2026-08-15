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

        $certificate = file_get_contents($this->certificatePath());

        if ($certificate === false) {
            throw ConfigurationException::missingCertificate($this->certificatePath());
        }

        if (! openssl_public_encrypt((string) $plaintext, $encrypted, $certificate, OPENSSL_PKCS1_PADDING)) {
            throw new ConfigurationException(
                'Failed to encrypt the initiator credential. The Safaricom certificate could not be parsed: '.
                (openssl_error_string() ?: 'unknown OpenSSL error'),
            );
        }

        return base64_encode($encrypted);
    }

    public function certificatePath(): string
    {
        $configured = $this->config->get('laravel-daraja.certificate_path');

        if (filled($configured)) {
            return (string) $configured;
        }

        $mode = Mode::fromConfig($this->config->get('laravel-daraja.mode'));

        $path = dirname(__DIR__, 2).'/certs/'.$mode->certificate();

        if (! is_readable($path)) {
            throw ConfigurationException::missingCertificate($path);
        }

        return $path;
    }
}
