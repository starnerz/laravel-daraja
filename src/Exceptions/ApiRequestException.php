<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Exceptions;

use Illuminate\Http\Client\Response;
use Throwable;

class ApiRequestException extends DarajaException
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        string $message,
        public readonly ?string $errorCode = null,
        public readonly ?string $requestId = null,
        public readonly int $status = 0,
        public readonly array $payload = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    public function docsUrl(): string
    {
        return self::DOCS.'/reference/error-codes/';
    }

    /**
     * The message stays Safaricom's own words, since applications store and
     * show it; the explanation and identifiers go to the log instead.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return array_filter([
            'docs' => $this->docsUrl(),
            'error_code' => $this->errorCode,
            'request_id' => $this->requestId,
            'status' => $this->status ?: null,
        ]);
    }

    /**
     * Build an exception from a failed Daraja response.
     *
     * Daraja reports failures in a few different shapes: a flat JSON error
     * object, a SOAP-style fault envelope, or occasionally plain text.
     */
    public static function fromResponse(Response $response, ?Throwable $previous = null): self
    {
        $status = $response->status();
        $body = $response->json();

        if (! is_array($body)) {
            return new self(
                'Safaricom Daraja: '.self::truncate($response->body()),
                status: $status,
                previous: $previous,
            );
        }

        if (isset($body['Envelope']['Body']['Fault']['faultstring'])) {
            return new self(
                'Safaricom Daraja: '.$body['Envelope']['Body']['Fault']['faultstring'],
                status: $status,
                payload: $body,
                previous: $previous,
            );
        }

        return self::fromPayload($body, $status, $previous);
    }

    /**
     * Build an exception from a decoded Daraja error payload.
     *
     * Some endpoints may return gateway errors in a successful HTTP response.
     *
     * @param  array<string, mixed>  $body
     */
    public static function fromPayload(array $body, int $status = 0, ?Throwable $previous = null): self
    {
        $message = $body['errorMessage']
            ?? $body['ResponseDescription']
            ?? $body['ResultDesc']
            ?? 'The Safaricom Daraja API returned an error.';

        return new self(
            'Safaricom Daraja: '.$message,
            errorCode: isset($body['errorCode']) ? (string) $body['errorCode'] : null,
            requestId: isset($body['responseId'])
                ? (string) $body['responseId']
                : (isset($body['requestId']) ? (string) $body['requestId'] : null),
            status: $status,
            payload: $body,
            previous: $previous,
        );
    }

    private static function truncate(string $body, int $length = 300): string
    {
        $body = trim($body);

        return mb_strlen($body) > $length
            ? mb_substr($body, 0, $length).'…'
            : $body;
    }
}
