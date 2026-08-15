<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Http;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Starnerz\LaravelDaraja\Enums\Mode;
use Starnerz\LaravelDaraja\Exceptions\AuthenticationException;
use Starnerz\LaravelDaraja\Exceptions\ConfigurationException;

/**
 * Obtains and caches the Daraja OAuth access token.
 *
 * Daraja tokens are valid for one hour. Rather than paying for a token request
 * on every API call (which is what v1 did, in every constructor), the token is
 * cached just short of its lifetime and shared across requests.
 */
final class TokenRepository
{
    public function __construct(
        private readonly Repository $config,
        private readonly CacheFactory $cache,
        private readonly HttpFactory $http,
    ) {}

    public function token(): string
    {
        return $this->store()->remember(
            $this->cacheKey(),
            $this->config->get('laravel-daraja.cache.token_ttl', 3540),
            fn (): string => $this->request(),
        );
    }

    /**
     * Discard the cached token, forcing the next call to fetch a fresh one.
     */
    public function forget(): void
    {
        $this->store()->forget($this->cacheKey());
    }

    private function request(): string
    {
        $key = $this->config->get('laravel-daraja.consumer_key');
        $secret = $this->config->get('laravel-daraja.consumer_secret');

        if (blank($key)) {
            throw ConfigurationException::missing('consumer_key');
        }

        if (blank($secret)) {
            throw ConfigurationException::missing('consumer_secret');
        }

        try {
            $response = $this->http
                ->baseUrl($this->host())
                ->withBasicAuth((string) $key, (string) $secret)
                ->timeout((int) $this->config->get('laravel-daraja.http.timeout', 30))
                ->connectTimeout((int) $this->config->get('laravel-daraja.http.connect_timeout', 10))
                ->get('oauth/v1/generate', ['grant_type' => 'client_credentials']);
        } catch (ConnectionException $e) {
            throw AuthenticationException::tokenRequestFailed($e->getMessage(), previous: $e);
        }

        if ($response->failed()) {
            throw AuthenticationException::tokenRequestFailed(
                (string) ($response->json('errorMessage') ?? $response->reason()),
                $response->status(),
            );
        }

        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            throw AuthenticationException::malformedTokenResponse();
        }

        return $token;
    }

    private function host(): string
    {
        $mode = Mode::fromConfig($this->config->get('laravel-daraja.mode'));

        return (string) $this->config->get("laravel-daraja.hosts.{$mode->value}");
    }

    private function cacheKey(): string
    {
        $prefix = $this->config->get('laravel-daraja.cache.prefix', 'daraja');
        $mode = $this->config->get('laravel-daraja.mode', 'sandbox');

        // Keyed by consumer key so multiple credentials never share a token.
        $fingerprint = md5((string) $this->config->get('laravel-daraja.consumer_key'));

        return "{$prefix}:token:{$mode}:{$fingerprint}";
    }

    private function store(): \Illuminate\Contracts\Cache\Repository
    {
        return $this->cache->store($this->config->get('laravel-daraja.cache.store'));
    }
}
