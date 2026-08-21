<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Http;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Log\LogManager;
use Starnerz\LaravelDaraja\Enums\Mode;
use Starnerz\LaravelDaraja\Events\DarajaRequestSending;
use Starnerz\LaravelDaraja\Events\DarajaResponseReceived;
use Starnerz\LaravelDaraja\Exceptions\ApiRequestException;
use Throwable;

/**
 * The single point through which every Daraja call is made.
 *
 * Built on Laravel's HTTP client so applications can drive the whole package
 * with Http::fake() in their own tests.
 */
final class DarajaClient
{
    /**
     * Payload keys that must never reach the log.
     *
     * @var list<string>
     */
    private const REDACTED = [
        'SecurityCredential',
        'Password',
        'InitiatorPassword',
        'consumer_key',
        'consumer_secret',
    ];

    public function __construct(
        private readonly Repository $config,
        private readonly TokenRepository $tokens,
        private readonly HttpFactory $http,
        private readonly LogManager $log,
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload  Usually keyed, but some Bill
     *                                            Manager endpoints take a list.
     * @return array<string, mixed>
     */
    public function post(string $endpoint, array $payload): array
    {
        return $this->send('POST', $endpoint, $payload);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->send('GET', $endpoint, $query);
    }

    /**
     * A pre-authenticated request against the configured Daraja host, for
     * endpoints that need options this client does not expose.
     */
    public function pendingRequest(): PendingRequest
    {
        $http = $this->config->get('laravel-daraja.http');

        return $this->http
            ->baseUrl($this->host())
            ->withToken($this->tokens->token())
            ->acceptJson()
            ->asJson()
            ->timeout((int) ($http['timeout'] ?? 30))
            ->connectTimeout((int) ($http['connect_timeout'] ?? 10))
            ->retry(
                times: (int) ($http['retries'] ?? 2) + 1,
                sleepMilliseconds: (int) ($http['retry_delay'] ?? 250),
                // Only connection problems and Safaricom-side faults are worth
                // retrying; a 4xx means the request itself was rejected.
                when: fn (Throwable $e): bool => $e instanceof ConnectionException,
                throw: false,
            );
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<string, mixed>
     */
    private function send(string $method, string $endpoint, array $data): array
    {
        // Redacted once and shared: the log and the event must never disagree
        // about what is safe to keep.
        $redacted = $this->redact($data);

        $this->logRequest($method, $endpoint, $redacted);
        DarajaRequestSending::dispatch($method, $endpoint, $redacted);

        $startedAt = microtime(true);

        try {
            $response = $this->pendingRequest()->send($method, $endpoint, [
                $method === 'GET' ? 'query' : 'json' => $data,
            ]);
        } catch (ConnectionException $e) {
            throw new ApiRequestException(
                "Could not reach the Safaricom Daraja API: {$e->getMessage()}",
                previous: $e,
            );
        }

        $elapsed = round((microtime(true) - $startedAt) * 1000, 2);
        $body = $response->json();

        $this->logResponse($endpoint, $response);
        DarajaResponseReceived::dispatch(
            $method,
            $endpoint,
            $response->status(),
            is_array($body) ? $this->redact($body) : $response->body(),
            $elapsed,
        );

        if ($response->failed()) {
            throw ApiRequestException::fromResponse($response);
        }

        return (array) $response->json();
    }

    private function host(): string
    {
        $mode = Mode::fromConfig($this->config->get('laravel-daraja.mode'));

        return (string) $this->config->get("laravel-daraja.hosts.{$mode->value}");
    }

    /**
     * @param  array<array-key, mixed>  $redacted  Already passed through redact()
     */
    private function logRequest(string $method, string $endpoint, array $redacted): void
    {
        if (! $this->config->get('laravel-daraja.logging.enabled')) {
            return;
        }

        $this->channel()->debug("Daraja request {$method} {$endpoint}", $redacted);
    }

    private function logResponse(string $endpoint, Response $response): void
    {
        if (! $this->config->get('laravel-daraja.logging.enabled')) {
            return;
        }

        $this->channel()->debug("Daraja response {$endpoint}", [
            'status' => $response->status(),
            'body' => is_array($response->json()) ? $this->redact($response->json()) : $response->body(),
        ]);
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function redact(array $data): array
    {
        foreach (self::REDACTED as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = '[redacted]';
            }
        }

        return $data;
    }

    private function channel(): \Psr\Log\LoggerInterface
    {
        return $this->log->channel($this->config->get('laravel-daraja.logging.channel'));
    }
}
