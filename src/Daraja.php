<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja;

use Closure;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Starnerz\LaravelDaraja\Apis\AccountBalance;
use Starnerz\LaravelDaraja\Apis\B2B;
use Starnerz\LaravelDaraja\Apis\B2BExpressCheckout;
use Starnerz\LaravelDaraja\Apis\B2C;
use Starnerz\LaravelDaraja\Apis\B2CHakikisha;
use Starnerz\LaravelDaraja\Apis\BillManager;
use Starnerz\LaravelDaraja\Apis\C2B;
use Starnerz\LaravelDaraja\Apis\DynamicQr;
use Starnerz\LaravelDaraja\Apis\LipaNaBonga;
use Starnerz\LaravelDaraja\Apis\MobileNumberValidation;
use Starnerz\LaravelDaraja\Apis\MpesaExpress;
use Starnerz\LaravelDaraja\Apis\PullTransactions;
use Starnerz\LaravelDaraja\Apis\Reversal;
use Starnerz\LaravelDaraja\Apis\StandingOrder;
use Starnerz\LaravelDaraja\Apis\Swap;
use Starnerz\LaravelDaraja\Apis\TransactionStatus;
use Starnerz\LaravelDaraja\Http\C2BHakikishaHandler;
use Starnerz\LaravelDaraja\Http\DarajaClient;
use Starnerz\LaravelDaraja\Http\TokenRepository;
use Starnerz\LaravelDaraja\Support\CallbackUrl;
use Starnerz\LaravelDaraja\Support\SecurityCredential;

/**
 * Entry point to the Daraja APIs.
 *
 * Each API is resolved from the container, so they can be swapped or decorated
 * in an application's own service provider.
 */
final class Daraja
{
    /**
     * Decides whether to accept a C2B payment during validation.
     *
     * @var (Closure(Data\Callbacks\C2BTransaction): (bool|string))|null
     */
    private ?Closure $c2bValidator = null;

    /**
     * @param  Repository|null  $config  Settings of this instance's own, from
     *                                   using(); null for the application's.
     */
    public function __construct(
        private readonly Container $app,
        private readonly ?Repository $config = null,
    ) {}

    /**
     * Register the callback that decides whether to accept a C2B payment.
     *
     * Return true to accept, false to reject with a generic error, or one of
     * Safaricom's C2B00011–C2B00016 codes to reject with a specific reason.
     * Safaricom expects an answer within about eight seconds, so keep the
     * callback fast and push slow work onto a queue.
     *
     * @param  Closure(Data\Callbacks\C2BTransaction): (bool|string)  $callback
     */
    public function validateC2BUsing(Closure $callback): void
    {
        $this->c2bValidator = $callback;
    }

    /**
     * @return (Closure(Data\Callbacks\C2BTransaction): (bool|string))|null
     */
    public function c2bValidator(): ?Closure
    {
        return $this->c2bValidator;
    }

    /**
     * Register the account-name lookup used by the C2B Hakikisha receiver.
     *
     * Safaricom calls it before a customer pays to a paybill, so the customer
     * sees whose account they are paying. Return the account name, or null when
     * the account number does not belong to the shortcode.
     *
     * @param  Closure(string, string): (?string)  $callback
     */
    public function resolveC2BAccountUsing(Closure $callback): void
    {
        $this->app->make(C2BHakikishaHandler::class)->resolveAccountUsing($callback);
    }

    /**
     * M-Pesa Express (STK Push).
     */
    public function stk(): MpesaExpress
    {
        return $this->make(MpesaExpress::class);
    }

    /**
     * Customer to Business.
     */
    public function c2b(): C2B
    {
        return $this->make(C2B::class);
    }

    /**
     * Check a mobile number against the holder's identity document.
     */
    public function mobileNumberValidation(): MobileNumberValidation
    {
        return $this->make(MobileNumberValidation::class);
    }

    /**
     * Check the date of a customer's most recent SIM swap.
     */
    public function simSwap(): Swap
    {
        return $this->make(Swap::class);
    }

    /**
     * Verify a recipient's name before making a B2C payout.
     */
    public function b2cHakikisha(): B2CHakikisha
    {
        return $this->make(B2CHakikisha::class);
    }

    /**
     * Business to Customer payouts.
     */
    public function b2c(): B2C
    {
        return $this->make(B2C::class);
    }

    /**
     * Business to Business transfers.
     */
    public function b2b(): B2B
    {
        return $this->make(B2B::class);
    }

    /**
     * Short code balance enquiries.
     */
    public function balance(): AccountBalance
    {
        return $this->make(AccountBalance::class);
    }

    /**
     * Transaction status enquiries.
     */
    public function transaction(): TransactionStatus
    {
        return $this->make(TransactionStatus::class);
    }

    /**
     * Transaction reversals.
     */
    public function reversal(): Reversal
    {
        return $this->make(Reversal::class);
    }

    /**
     * B2B Express Checkout — USSD push to a till operator.
     */
    public function b2bExpress(): B2BExpressCheckout
    {
        return $this->make(B2BExpressCheckout::class);
    }

    /**
     * Dynamic QR code generation.
     */
    public function qr(): DynamicQr
    {
        return $this->make(DynamicQr::class);
    }

    /**
     * M-Pesa Ratiba standing orders.
     */
    public function standingOrder(): StandingOrder
    {
        return $this->make(StandingOrder::class);
    }

    /**
     * Bill Manager invoicing and reconciliation.
     */
    public function billManager(): BillManager
    {
        return $this->make(BillManager::class);
    }

    /**
     * Pull Transactions reconciliation.
     */
    public function pull(): PullTransactions
    {
        return $this->make(PullTransactions::class);
    }

    /**
     * Lipa na Bonga loyalty point payments.
     */
    public function bonga(): LipaNaBonga
    {
        return $this->make(LipaNaBonga::class);
    }

    /**
     * The underlying HTTP client, for endpoints not yet wrapped by the package.
     */
    public function client(): DarajaClient
    {
        return $this->make(DarajaClient::class);
    }

    /**
     * The cached OAuth access token used to authorise requests.
     */
    public function accessToken(): string
    {
        return $this->make(TokenRepository::class)->token();
    }

    /**
     * Discard the cached access token.
     */
    public function forgetAccessToken(): void
    {
        $this->make(TokenRepository::class)->forget();
    }

    /**
     * Encrypt an initiator password into a SecurityCredential.
     */
    public function securityCredential(?string $plaintext = null): string
    {
        return $this->make(SecurityCredential::class)->generate($plaintext);
    }

    /**
     * A Daraja that makes every call with these settings in place of the
     * configured ones, leaving the application's configuration untouched.
     *
     * Keys are those of config/laravel-daraja.php, nested or in dot notation:
     * `consumer_key`, `mode`, `stk.pass_key`, `initiator.credential` and so on.
     * Anything not given falls back to configuration as it stands now.
     *
     * This is how one application calls Daraja for several merchants. Nothing
     * is set globally, so there is nothing to restore, and a queued job or a
     * long-lived worker can never inherit another merchant's credentials.
     *
     * `$connection` names whose credentials these are. It is carried on
     * DarajaRequestSending and DarajaResponseReceived, so a listener can tell
     * which merchant a call was made for; the payload alone does not always say.
     *
     * Every API on the returned instance is built directly rather than resolved
     * from the container, so a binding that decorates an API does not apply to
     * it. A decorator that read configuration globally would otherwise send one
     * merchant's call with another's credentials.
     *
     * @param  array<string, mixed>  $settings
     */
    public function using(array $settings, ?string $connection = null): self
    {
        $current = $this->config?->get('laravel-daraja')
            ?? $this->app->make('config')->get('laravel-daraja', []);

        $merged = array_replace_recursive((array) $current, Arr::undot($settings));

        if ($connection !== null) {
            $merged['connection'] = $connection;
        }

        return new self($this->app, new ConfigRepository(['laravel-daraja' => $merged]));
    }

    /**
     * The connection this instance makes its calls for, if it was named.
     */
    public function connection(): ?string
    {
        $connection = $this->config?->get('laravel-daraja.connection');

        return is_string($connection) ? $connection : null;
    }

    /**
     * Resolve one of the package's services, against this instance's settings
     * when it has its own.
     *
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return T
     */
    private function make(string $class): object
    {
        if ($this->config === null) {
            return $this->app->make($class);
        }

        $config = $this->config;

        // The package's own services are bound by class alone, so passing
        // parameters builds a fresh, unshared instance around these settings.
        $tokens = $this->app->make(TokenRepository::class, ['config' => $config]);
        $client = $this->app->make(DarajaClient::class, ['config' => $config, 'tokens' => $tokens]);
        $credential = $this->app->make(SecurityCredential::class, ['config' => $config]);

        $built = match ($class) {
            TokenRepository::class => $tokens,
            DarajaClient::class => $client,
            SecurityCredential::class => $credential,
            default => new $class($client, $config, $this->app->make(CallbackUrl::class), $credential),
        };

        /** @var T $built */
        return $built;
    }
}
