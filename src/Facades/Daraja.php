<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Starnerz\LaravelDaraja\Apis\MpesaExpress stk()
 * @method static \Starnerz\LaravelDaraja\Apis\C2B c2b()
 * @method static \Starnerz\LaravelDaraja\Apis\B2C b2c()
 * @method static \Starnerz\LaravelDaraja\Apis\B2B b2b()
 * @method static \Starnerz\LaravelDaraja\Apis\AccountBalance balance()
 * @method static \Starnerz\LaravelDaraja\Apis\TransactionStatus transaction()
 * @method static \Starnerz\LaravelDaraja\Apis\Reversal reversal()
 * @method static \Starnerz\LaravelDaraja\Apis\B2BExpressCheckout b2bExpress()
 * @method static \Starnerz\LaravelDaraja\Apis\DynamicQr qr()
 * @method static \Starnerz\LaravelDaraja\Apis\StandingOrder standingOrder()
 * @method static \Starnerz\LaravelDaraja\Apis\BillManager billManager()
 * @method static \Starnerz\LaravelDaraja\Apis\PullTransactions pull()
 * @method static \Starnerz\LaravelDaraja\Apis\LipaNaBonga bonga()
 * @method static \Starnerz\LaravelDaraja\Http\DarajaClient client()
 * @method static string accessToken()
 * @method static void forgetAccessToken()
 * @method static string securityCredential(?string $plaintext = null)
 *
 * @see \Starnerz\LaravelDaraja\Daraja
 */
class Daraja extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Starnerz\LaravelDaraja\Daraja::class;
    }
}
