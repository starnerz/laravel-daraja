<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Data;

/**
 * A generated dynamic QR code.
 *
 * Note this API reuses the name "ResponseCode" for something that is not a
 * status flag — it carries an identifier string, so there is no accepted()
 * helper here as there is on the other responses.
 */
final readonly class DynamicQrResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $responseCode,
        public string $requestId,
        public string $responseDescription,
        public string $qrCode,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            responseCode: (string) ($data['ResponseCode'] ?? ''),
            requestId: (string) ($data['RequestID'] ?? ''),
            responseDescription: (string) ($data['ResponseDescription'] ?? ''),
            qrCode: (string) ($data['QRCode'] ?? ''),
            raw: $data,
        );
    }

    /**
     * The QR code as raw PNG bytes, ready to store or stream.
     */
    public function png(): string
    {
        $decoded = base64_decode($this->qrCode, true);

        return $decoded === false ? '' : $decoded;
    }

    /**
     * The QR code as a data URI, for embedding directly in an <img> tag.
     */
    public function dataUri(): string
    {
        return 'data:image/png;base64,'.$this->qrCode;
    }
}
