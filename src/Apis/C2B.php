<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Apis;

use Starnerz\LaravelDaraja\Data\Acknowledgement;
use Starnerz\LaravelDaraja\Enums\CommandId;
use Starnerz\LaravelDaraja\Enums\Mode;
use Starnerz\LaravelDaraja\Exceptions\DarajaException;
use Starnerz\LaravelDaraja\Support\PhoneNumber;

/**
 * Customer to Business: receive payments made directly to your short code.
 *
 * Endpoints confirmed as v2 against the Daraja portal on 15 Aug 2026. The
 * difference from v1 is the callback payload: v1 sent a SHA-256 hashed MSISDN,
 * v2 sends a masked one ("2547 ***** 126").
 */
final class C2B extends Api
{
    private const REGISTER_ENDPOINT = 'mpesa/c2b/v2/registerurl';

    private const SIMULATE_ENDPOINT = 'mpesa/c2b/v2/simulate';

    /**
     * Register the URLs Safaricom calls when a customer pays your short code.
     *
     * @param  string  $responseType  "Completed" or "Cancelled" — how Safaricom
     *                                should treat a payment when your validation
     *                                URL is unreachable.
     */
    public function registerUrls(
        ?string $confirmationUrl = null,
        ?string $validationUrl = null,
        string $responseType = 'Completed',
        ?string $shortCode = null,
    ): Acknowledgement {
        $response = $this->client->post(self::REGISTER_ENDPOINT, [
            'ShortCode' => $this->initiatorShortCode($shortCode),
            'ResponseType' => $responseType,
            'ConfirmationURL' => $this->urls->resolve(
                $confirmationUrl ?? $this->setting('urls.c2b.confirmation'),
                'urls.c2b.confirmation',
            ),
            'ValidationURL' => $this->urls->resolve(
                $validationUrl ?? $this->setting('urls.c2b.validation'),
                'urls.c2b.validation',
            ),
        ]);

        return Acknowledgement::fromArray($response);
    }

    /**
     * Simulate a customer paying your Pay Bill. Sandbox only.
     */
    public function simulatePayBill(
        string $phone,
        int|float|string $amount,
        string $reference,
        ?string $shortCode = null,
    ): Acknowledgement {
        return $this->simulate(CommandId::CustomerPayBillOnline, $phone, $amount, $reference, $shortCode);
    }

    /**
     * Simulate a customer paying your till. Sandbox only.
     */
    public function simulateBuyGoods(
        string $phone,
        int|float|string $amount,
        string $reference = '',
        ?string $shortCode = null,
    ): Acknowledgement {
        return $this->simulate(CommandId::CustomerBuyGoodsOnline, $phone, $amount, $reference, $shortCode);
    }

    private function simulate(
        CommandId $command,
        string $phone,
        int|float|string $amount,
        string $reference,
        ?string $shortCode,
    ): Acknowledgement {
        if (Mode::fromConfig($this->setting('mode'))->isLive()) {
            throw new DarajaException(
                'C2B simulation is only available in the sandbox. Set [laravel-daraja.mode] to "sandbox" to use it.',
            );
        }

        $response = $this->client->post(self::SIMULATE_ENDPOINT, [
            'ShortCode' => $this->initiatorShortCode($shortCode),
            'CommandID' => $command->value,
            'Amount' => $this->amount($amount),
            'Msisdn' => PhoneNumber::normalise($phone),
            'BillRefNumber' => $reference,
        ]);

        return Acknowledgement::fromArray($response);
    }
}
