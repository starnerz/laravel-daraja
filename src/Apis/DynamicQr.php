<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Apis;

use Starnerz\LaravelDaraja\Data\DynamicQrResponse;
use Starnerz\LaravelDaraja\Enums\QrTransactionType;

/**
 * Dynamic QR: generate a QR code that M-Pesa app users scan to capture the
 * till number and amount, then authorise payment.
 *
 * Confirmed against the Daraja portal on 15 Aug 2026.
 */
final class DynamicQr extends Api
{
    private const ENDPOINT = 'mpesa/qrcode/v1/generate';

    /**
     * @param  string  $merchantName  Your company or M-Pesa merchant name
     * @param  string  $reference  Transaction reference
     * @param  string|null  $creditPartyIdentifier  Mobile number, business number,
     *                                              agent till, pay bill or merchant
     *                                              buy goods. Defaults to the
     *                                              configured short code.
     * @param  int  $size  QR image size in pixels; the image is always square
     */
    public function generate(
        string $merchantName,
        string $reference,
        int|float|string $amount,
        QrTransactionType $type = QrTransactionType::BuyGoods,
        ?string $creditPartyIdentifier = null,
        int $size = 300,
    ): DynamicQrResponse {
        $response = $this->client->post(self::ENDPOINT, [
            'MerchantName' => $merchantName,
            'RefNo' => $reference,
            'Amount' => (int) $this->amount($amount),
            'TrxCode' => $type->value,
            'CPI' => $this->initiatorShortCode($creditPartyIdentifier),
            'Size' => (string) $size,
        ]);

        return DynamicQrResponse::fromArray($response);
    }
}
