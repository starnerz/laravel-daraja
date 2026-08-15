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

        $message = $body['errorMessage']
            ?? $body['ResponseDescription']
            ?? $body['ResultDesc']
            ?? self::truncate($response->body());

        return new self(
            'Safaricom Daraja: '.$message,
            errorCode: isset($body['errorCode']) ? (string) $body['errorCode'] : null,
            requestId: isset($body['requestId']) ? (string) $body['requestId'] : null,
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
