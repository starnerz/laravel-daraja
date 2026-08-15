<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Apis;

use Illuminate\Support\Str;
use Starnerz\LaravelDaraja\Data\UssdPushResponse;

/**
 * B2B Express Checkout, also called USSD Push to Till.
 *
 * Lets a vendor prompt a fellow merchant to pay from their till into the
 * vendor's pay bill. The merchant authorises on their handset with an
 * operator ID and PIN.
 *
 * Confirmed against the Daraja portal on 15 Aug 2026. Note this API sits
 * outside the /mpesa/ path and uses camelCase field names — it does not follow
 * the conventions of the rest of Daraja.
 */
final class B2BExpressCheckout extends Api
{
    private const ENDPOINT = 'v1/ussdpush/get-msisdn';

    /**
     * Push a payment prompt to a merchant's till operator.
     *
     * @param  string  $primaryShortCode  Debit party — the paying merchant's till
     * @param  string  $receiverShortCode  Credit party — your pay bill
     * @param  string  $paymentRef  Shown to the merchant in the prompt text
     * @param  string|null  $partnerName  Your organisation's friendly name, as the
     *                                    merchant knows it
     * @param  string|null  $requestRefId  Unique per request; generated if omitted
     */
    public function push(
        string $primaryShortCode,
        string $receiverShortCode,
        int|float|string $amount,
        string $paymentRef,
        ?string $partnerName = null,
        ?string $callbackUrl = null,
        ?string $requestRefId = null,
    ): UssdPushResponse {
        $response = $this->client->post(self::ENDPOINT, [
            'primaryShortCode' => $primaryShortCode,
            'receiverShortCode' => $receiverShortCode,
            'amount' => $this->amount($amount),
            'paymentRef' => $paymentRef,
            'callbackUrl' => $this->urls->resolve(
                $callbackUrl ?? $this->setting('urls.result.b2b_express'),
                'urls.result.b2b_express',
            ),
            'partnerName' => $partnerName ?? (string) $this->setting('partner_name'),
            'RequestRefID' => $requestRefId ?? (string) Str::uuid(),
        ]);

        return UssdPushResponse::fromArray($response);
    }
}
