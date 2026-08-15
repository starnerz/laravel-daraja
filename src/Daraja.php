<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja;

use Illuminate\Contracts\Container\Container;
use Starnerz\LaravelDaraja\Apis\AccountBalance;
use Starnerz\LaravelDaraja\Apis\B2B;
use Starnerz\LaravelDaraja\Apis\B2BExpressCheckout;
use Starnerz\LaravelDaraja\Apis\B2C;
use Starnerz\LaravelDaraja\Apis\BillManager;
use Starnerz\LaravelDaraja\Apis\C2B;
use Starnerz\LaravelDaraja\Apis\DynamicQr;
use Starnerz\LaravelDaraja\Apis\LipaNaBonga;
use Starnerz\LaravelDaraja\Apis\MpesaExpress;
use Starnerz\LaravelDaraja\Apis\PullTransactions;
use Starnerz\LaravelDaraja\Apis\Reversal;
use Starnerz\LaravelDaraja\Apis\StandingOrder;
use Starnerz\LaravelDaraja\Apis\TransactionStatus;
use Starnerz\LaravelDaraja\Http\DarajaClient;
use Starnerz\LaravelDaraja\Http\TokenRepository;
use Starnerz\LaravelDaraja\Support\SecurityCredential;

/**
 * Entry point to the Daraja APIs.
 *
 * Each API is resolved from the container, so they can be swapped or decorated
 * in an application's own service provider.
 */
final class Daraja
{
    public function __construct(private readonly Container $app) {}

    /**
     * M-Pesa Express (STK Push).
     */
    public function stk(): MpesaExpress
    {
        return $this->app->make(MpesaExpress::class);
    }

    /**
     * Customer to Business.
     */
    public function c2b(): C2B
    {
        return $this->app->make(C2B::class);
    }

    /**
     * Business to Customer payouts.
     */
    public function b2c(): B2C
    {
        return $this->app->make(B2C::class);
    }

    /**
     * Business to Business transfers.
     */
    public function b2b(): B2B
    {
        return $this->app->make(B2B::class);
    }

    /**
     * Short code balance enquiries.
     */
    public function balance(): AccountBalance
    {
        return $this->app->make(AccountBalance::class);
    }

    /**
     * Transaction status enquiries.
     */
    public function transaction(): TransactionStatus
    {
        return $this->app->make(TransactionStatus::class);
    }

    /**
     * Transaction reversals.
     */
    public function reversal(): Reversal
    {
        return $this->app->make(Reversal::class);
    }

    /**
     * B2B Express Checkout — USSD push to a till operator.
     */
    public function b2bExpress(): B2BExpressCheckout
    {
        return $this->app->make(B2BExpressCheckout::class);
    }

    /**
     * Dynamic QR code generation.
     */
    public function qr(): DynamicQr
    {
        return $this->app->make(DynamicQr::class);
    }

    /**
     * M-Pesa Ratiba standing orders.
     */
    public function standingOrder(): StandingOrder
    {
        return $this->app->make(StandingOrder::class);
    }

    /**
     * Bill Manager invoicing and reconciliation.
     */
    public function billManager(): BillManager
    {
        return $this->app->make(BillManager::class);
    }

    /**
     * Pull Transactions reconciliation.
     */
    public function pull(): PullTransactions
    {
        return $this->app->make(PullTransactions::class);
    }

    /**
     * Lipa na Bonga loyalty point payments.
     */
    public function bonga(): LipaNaBonga
    {
        return $this->app->make(LipaNaBonga::class);
    }

    /**
     * The underlying HTTP client, for endpoints not yet wrapped by the package.
     */
    public function client(): DarajaClient
    {
        return $this->app->make(DarajaClient::class);
    }

    /**
     * The cached OAuth access token used to authorise requests.
     */
    public function accessToken(): string
    {
        return $this->app->make(TokenRepository::class)->token();
    }

    /**
     * Discard the cached access token.
     */
    public function forgetAccessToken(): void
    {
        $this->app->make(TokenRepository::class)->forget();
    }

    /**
     * Encrypt an initiator password into a SecurityCredential.
     */
    public function securityCredential(?string $plaintext = null): string
    {
        return $this->app->make(SecurityCredential::class)->generate($plaintext);
    }
}
