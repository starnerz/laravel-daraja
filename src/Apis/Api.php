<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Apis;

use Illuminate\Contracts\Config\Repository;
use Starnerz\LaravelDaraja\Exceptions\ConfigurationException;
use Starnerz\LaravelDaraja\Http\DarajaClient;
use Starnerz\LaravelDaraja\Support\CallbackUrl;
use Starnerz\LaravelDaraja\Support\SecurityCredential;

abstract class Api
{
    public function __construct(
        protected readonly DarajaClient $client,
        protected readonly Repository $config,
        protected readonly CallbackUrl $urls,
        protected readonly SecurityCredential $credential,
    ) {}

    protected function setting(string $key, mixed $default = null): mixed
    {
        return $this->config->get("laravel-daraja.{$key}", $default);
    }

    /**
     * The short code transactions are initiated from, unless overridden.
     */
    protected function initiatorShortCode(?string $override = null): string
    {
        $shortCode = $override ?? $this->setting('initiator.short_code');

        if (blank($shortCode)) {
            throw ConfigurationException::missing('initiator.short_code');
        }

        return (string) $shortCode;
    }

    protected function initiatorName(?string $override = null): string
    {
        $name = $override ?? $this->setting('initiator.name');

        if (blank($name)) {
            throw ConfigurationException::missing('initiator.name');
        }

        return (string) $name;
    }

    /**
     * Safaricom expects amounts as whole shillings.
     */
    protected function amount(int|float|string $amount): string
    {
        return (string) (int) round((float) $amount);
    }
}
