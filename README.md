# Laravel Daraja

[![Tests](https://github.com/starnerz/laravel-daraja/actions/workflows/tests.yml/badge.svg)](https://github.com/starnerz/laravel-daraja/actions/workflows/tests.yml)
[![Static analysis](https://github.com/starnerz/laravel-daraja/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/starnerz/laravel-daraja/actions/workflows/static-analysis.yml)
[![Latest version](https://img.shields.io/packagist/v/starnerz/laravel-daraja.svg)](https://packagist.org/packages/starnerz/laravel-daraja)
[![Downloads](https://img.shields.io/packagist/dt/starnerz/laravel-daraja.svg)](https://packagist.org/packages/starnerz/laravel-daraja)
[![License](https://img.shields.io/packagist/l/starnerz/laravel-daraja.svg)](LICENSE)

Every Safaricom M-Pesa Daraja API, as ordinary Laravel code. Typed responses,
cached tokens, and callbacks you can actually test.

📖 **[Documentation](https://starnerz.github.io/daraja-docs/)**

## Requirements

- PHP 8.3+
- Laravel 12 or 13

## Installation

```bash
composer require starnerz/laravel-daraja
```

```dotenv
DARAJA_MODE=sandbox
DARAJA_CONSUMER_KEY=your-consumer-key
DARAJA_CONSUMER_SECRET=your-consumer-secret
DARAJA_STK_SHORTCODE=174379
DARAJA_STK_PASS_KEY=your-passkey
DARAJA_STK_CALLBACK_URL=https://your-domain/daraja/stk
```

Verify the credentials:

```bash
php artisan daraja:token
```

## Prompt a customer to pay

```php
use Starnerz\LaravelDaraja\Facades\Daraja;

$response = Daraja::stk()->push(
    phone: '0712345678',       // any Kenyan format
    amount: 1500,
    accountReference: 'INV-001',
);

$response->accepted();          // Safaricom sent the prompt
$response->checkoutRequestId;   // identifies this attempt
```

The payment result arrives later on your callback URL:

```php
use Starnerz\LaravelDaraja\Events\StkCallbackReceived;

Event::listen(function (StkCallbackReceived $event) {
    if ($event->callback->successful()) {
        Order::markPaid(
            $event->callback->checkoutRequestId,
            $event->callback->receipt(),
        );
    }
});
```

## Supported APIs

| | |
|---|---|
| M-Pesa Express | `Daraja::stk()->push()` / `->query()` |
| Customer to Business | `Daraja::c2b()->registerUrls()` / `->simulatePayBill()` |
| Business to Customer | `Daraja::b2c()->business()` / `->salary()` / `->promotion()` |
| Business to Pochi | `Daraja::b2c()->pochi()` |
| Business to Business | `Daraja::b2b()->payBill()` / `->buyGoods()` / `->accountTopUp()` |
| B2B Express Checkout | `Daraja::b2bExpress()->push()` |
| Account Balance | `Daraja::balance()->query()` |
| Transaction Status | `Daraja::transaction()->query()` |
| Reversal | `Daraja::reversal()->reverse()` |
| Dynamic QR | `Daraja::qr()->generate()` |
| M-Pesa Ratiba | `Daraja::standingOrder()->create()` |
| Bill Manager | `Daraja::billManager()->invoice()` |
| Pull Transactions | `Daraja::pull()->query()` |
| Lipa na Bonga | `Daraja::bonga()->redeem()` |

## Testing

Built on Laravel's HTTP client, so `Http::fake()` drives the whole package — no
sandbox credentials, network or handset required.

```php
Http::fake([
    '*/oauth/*' => Http::response(['access_token' => 'test']),
    '*/mpesa/stkpush/*' => Http::response(['ResponseCode' => '0']),
]);
```

See the [testing guide](https://starnerz.github.io/daraja-docs/guides/testing/).

## Upgrading from v1

v2 is a rewrite: the facade is now `Daraja`, responses are typed objects, and
several endpoints moved (C2B to v2, B2C to v3). See the
[upgrade guide](https://starnerz.github.io/daraja-docs/upgrade/v1-to-v2/).

Laravel 10 and 11 are not supported — both are past end of life and carry
unpatched advisories. Applications on those versions should stay on `^1.0`.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

Report vulnerabilities to stanleykimathi@gmail.com rather than the issue
tracker. See [SECURITY.md](SECURITY.md).

## Licence

MIT. See [LICENSE](LICENSE).
