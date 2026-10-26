<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Http;

use Closure;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository;
use Psr\SimpleCache\InvalidArgumentException;
use Random\RandomException;
use Starnerz\LaravelDaraja\Exceptions\ConfigurationException;

/**
 * Implements the partner-side C2B Hakikisha token and account lookup contract.
 *
 * A singleton, so the resolver registered at boot survives every request
 * under Octane. Tokens live in the configured cache store, which must be
 * shared when the application runs on more than one server.
 */
final class C2BHakikishaHandler
{
    /**
     * @var Closure(string, string): (?string)|null
     */
    private ?Closure $accountResolver = null;

    public function __construct(
        private readonly Repository $config,
        private readonly CacheFactory $cache,
    ) {}

    /**
     * @param  Closure(string, string): (?string)  $callback
     */
    public function resolveAccountUsing(Closure $callback): void
    {
        $this->accountResolver = $callback;
    }

    /**
     * @return array{0: array<string, mixed>, 1: int}
     *
     * @throws RandomException
     */
    public function issueToken(?string $authorization, ?string $grantType): array
    {
        if ($grantType !== 'client_credentials') {
            return [[
                'error' => 'unsupported_grant_type',
                'errorMessage' => 'The grant_type must be client_credentials.',
            ], 400];
        }

        [$username, $password] = $this->credentials();
        $provided = $this->basicCredentials($authorization);

        if ($provided === null
            || ! hash_equals($username, $provided[0])
            || ! hash_equals($password, $provided[1])) {
            return [[
                'error' => 'unauthorized',
                'errorMessage' => 'Invalid client credentials.',
            ], 401];
        }

        $token = bin2hex(random_bytes(32));
        $ttl = max(1, (int) $this->config->get('laravel-daraja.c2b_hakikisha.token_ttl', 3599));

        $this->cacheStore()->put($this->tokenKey($token), true, $ttl);

        return [[
            'access_token' => $token,
            'expires_in' => $ttl,
        ], 200];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: array<string, mixed>, 1: int}
     *
     * @throws InvalidArgumentException
     */
    public function validate(?string $authorization, array $payload): array
    {
        $requestId = is_scalar($payload['requestId'] ?? null) ? (string) $payload['requestId'] : '';
        $token = $this->bearerToken($authorization);

        // Authenticated first, so a caller without a token learns nothing
        // about what a well-formed request looks like.
        if ($token === null || ! $this->cacheStore()->has($this->tokenKey($token))) {
            return [
                ['requestId' => $requestId, 'errorMessage' => 'Invalid access token.'],
                401,
            ];
        }

        if ($requestId === ''
            || ! is_scalar($payload['timestamp'] ?? null)
            || ! is_scalar($payload['accountNumber'] ?? null)
            || ! is_scalar($payload['shortcode'] ?? null)
            || trim((string) $payload['timestamp']) === ''
            || trim((string) $payload['accountNumber']) === ''
            || trim((string) $payload['shortcode']) === '') {
            return [[
                'requestId' => $requestId,
                'errorMessage' => 'Missing or malformed required fields in the request.',
            ], 422];
        }

        if ($this->accountResolver === null) {
            throw new ConfigurationException(
                'No C2B Hakikisha account resolver is registered. Call Daraja::resolveC2BAccountUsing() in a service provider.',
            );
        }

        $accountNumber = trim((string) $payload['accountNumber']);
        $shortcode = trim((string) $payload['shortcode']);
        $accountName = ($this->accountResolver)($accountNumber, $shortcode);

        if (! is_string($accountName) || trim($accountName) === '') {
            return [
                ['requestId' => $requestId, 'errorMessage' => 'Invalid account number.'],
                400,
            ];
        }

        return [[
            'requestId' => $requestId,
            'timestamp' => time(),
            'accountName' => trim($accountName),
            'accountNumber' => $accountNumber,
            'shortcode' => $shortcode,
        ], 200];
    }

    /**
     * @return array{string, string}
     */
    private function credentials(): array
    {
        $username = $this->config->get('laravel-daraja.c2b_hakikisha.username');
        $password = $this->config->get('laravel-daraja.c2b_hakikisha.password');

        if (! is_string($username) || trim($username) === '') {
            throw ConfigurationException::missing('c2b_hakikisha.username');
        }

        if (! is_string($password) || $password === '') {
            throw ConfigurationException::missing('c2b_hakikisha.password');
        }

        return [$username, $password];
    }

    /**
     * @return array{string, string}|null
     */
    private function basicCredentials(?string $authorization): ?array
    {
        if ($authorization === null || ! preg_match('/^Basic\s+(.+)$/i', trim($authorization), $matches)) {
            return null;
        }

        $decoded = base64_decode($matches[1], true);

        if (! is_string($decoded) || ! str_contains($decoded, ':')) {
            return null;
        }

        [$username, $password] = explode(':', $decoded, 2);

        return [$username, $password];
    }

    private function bearerToken(?string $authorization): ?string
    {
        if ($authorization === null || ! preg_match('/^Bearer\s+(\S+)$/i', trim($authorization), $matches)) {
            return null;
        }

        return $matches[1];
    }

    private function tokenKey(string $token): string
    {
        $prefix = (string) $this->config->get('laravel-daraja.cache.prefix', 'daraja');

        return "{$prefix}:c2b-hakikisha:".hash('sha256', $token);
    }

    private function cacheStore(): \Illuminate\Contracts\Cache\Repository
    {
        $store = $this->config->get('laravel-daraja.cache.store');

        return $this->cache->store(is_string($store) && $store !== '' ? $store : null);
    }
}
