<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Apis;

use Illuminate\Support\Str;
use Starnerz\LaravelDaraja\Data\StkPushResponse;
use Starnerz\LaravelDaraja\Data\StkQueryResponse;
use Starnerz\LaravelDaraja\Enums\TransactionType;
use Starnerz\LaravelDaraja\Exceptions\ConfigurationException;
use Starnerz\LaravelDaraja\Support\PhoneNumber;

/**
 * M-Pesa Express, better known as STK Push: prompts a customer to authorise a
 * payment on their handset.
 */
final class MpesaExpress extends Api
{
    private const PUSH_ENDPOINT = 'mpesa/stkpush/v1/processrequest';

    private const QUERY_ENDPOINT = 'mpesa/stkpushquery/v1/query';

    /**
     * Push a payment prompt to a customer's phone.
     *
     * @param  string  $phone  Any Kenyan format: 0712…, +254712…, 254712…
     * @param  string|null  $description  Truncated to the 13 characters Daraja allows
     * @param  string|null  $partyB  The credit party. For Buy Goods this is the till
     *                               number, which differs from the BusinessShortCode
     *                               (the HO/store number). Defaults to the short code.
     */
    public function push(
        string $phone,
        int|float|string $amount,
        string $accountReference,
        ?string $description = null,
        ?string $callbackUrl = null,
        ?string $shortCode = null,
        TransactionType $type = TransactionType::PayBill,
        ?string $partyB = null,
    ): StkPushResponse {
        $shortCode = $this->shortCode($shortCode);
        $timestamp = $this->timestamp();
        $phone = PhoneNumber::normalise($phone);

        $response = $this->client->post(self::PUSH_ENDPOINT, [
            'BusinessShortCode' => $shortCode,
            'Password' => $this->password($shortCode, $timestamp),
            'Timestamp' => $timestamp,
            'TransactionType' => $type->value,
            'Amount' => $this->amount($amount),
            'PartyA' => $phone,
            'PartyB' => $partyB ?? $shortCode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $this->urls->resolve(
                $callbackUrl ?? $this->setting('stk.callback_url'),
                'stk.callback_url',
            ),
            'AccountReference' => Str::limit($accountReference, 12, ''),
            'TransactionDesc' => Str::limit($description ?? $accountReference, 13, ''),
        ]);

        return StkPushResponse::fromArray($response);
    }

    /**
     * Ask Safaricom what happened to a push you already sent.
     */
    public function query(string $checkoutRequestId, ?string $shortCode = null): StkQueryResponse
    {
        $shortCode = $this->shortCode($shortCode);
        $timestamp = $this->timestamp();

        $response = $this->client->post(self::QUERY_ENDPOINT, [
            'BusinessShortCode' => $shortCode,
            'Password' => $this->password($shortCode, $timestamp),
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ]);

        return StkQueryResponse::fromArray($response);
    }

    /**
     * base64(ShortCode + PassKey + Timestamp), as required by Daraja.
     */
    private function password(string $shortCode, string $timestamp): string
    {
        $passKey = $this->setting('stk.pass_key');

        if (blank($passKey)) {
            throw ConfigurationException::missing('stk.pass_key');
        }

        return base64_encode($shortCode.$passKey.$timestamp);
    }

    private function shortCode(?string $override = null): string
    {
        $shortCode = $override ?? $this->setting('stk.short_code');

        if (blank($shortCode)) {
            throw ConfigurationException::missing('stk.short_code');
        }

        return (string) $shortCode;
    }

    /**
     * Daraja requires East Africa Time regardless of the app's timezone.
     */
    private function timestamp(): string
    {
        return now()->setTimezone('Africa/Nairobi')->format('YmdHis');
    }
}
