# Laravel Daraja

Safaricom M-Pesa Daraja APIs, as ordinary Laravel code. Twenty-one APIs —
STK Push, C2B, B2C, B2B, Dynamic QR, M-Pesa Ratiba, Hakikisha and the rest —
with typed responses, cached tokens, and callbacks you can actually test.

📖 **[Documentation: laraveldaraja.com](https://laraveldaraja.com/)** — new here? Start with the
[STK Push tutorial](https://laraveldaraja.com/tutorials/stk-push/).

[![Tests](https://github.com/starnerz/laravel-daraja/actions/workflows/tests.yml/badge.svg)](https://github.com/starnerz/laravel-daraja/actions/workflows/tests.yml)
[![Static analysis](https://github.com/starnerz/laravel-daraja/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/starnerz/laravel-daraja/actions/workflows/static-analysis.yml)
[![Latest version](https://img.shields.io/packagist/v/starnerz/laravel-daraja.svg)](https://packagist.org/packages/starnerz/laravel-daraja)
[![Downloads](https://img.shields.io/packagist/dt/starnerz/laravel-daraja.svg)](https://packagist.org/packages/starnerz/laravel-daraja)
[![License](https://img.shields.io/packagist/l/starnerz/laravel-daraja.svg)](LICENSE)

## At a glance

```bash
composer require starnerz/laravel-daraja
```

```php
use Starnerz\LaravelDaraja\Facades\Daraja;

$response = Daraja::stk()->push(phone: '0712345678', amount: 1500, accountReference: 'INV-001');
```

```php
use Starnerz\LaravelDaraja\Events\StkCallbackReceived;

Event::listen(function (StkCallbackReceived $event) {
    if ($event->callback->successful()) {
        Order::markPaid($event->callback->checkoutRequestId, $event->callback->receipt());
    }
});
```

Requires PHP 8.3+ and Laravel 12 or 13. The credentials, `.env` keys and
callback routes are in [Installation](https://laraveldaraja.com/getting-started/installation/) and
[Configuration](https://laraveldaraja.com/getting-started/configuration/).

## Before you take real money

The snippet above works in sandbox. These are where M-Pesa integrations break
in production, and each has a page:

- **Callbacks that never arrive, or arrive twice.** A payment can succeed with
  no callback reaching you, a confirmation can be delivered more than once, and
  Pull Transactions only reaches back 48 hours.
  [Reconciling payments](https://laraveldaraja.com/guides/reconciliation/)
- **Trusting a callback.** Safaricom does not sign callbacks, so each one is a claim to
  check, not a record. [Handling callbacks](https://laraveldaraja.com/guides/callbacks/)
- **Going live.** Production credentials, operator roles and Safaricom's
  approval. [Going live](https://laraveldaraja.com/guides/going-live/)
- **Testing without a phone.** `Http::fake()` drives the whole package.
  [Testing](https://laraveldaraja.com/guides/testing/)

## Tutorials

Complete integrations, from an empty Laravel application to a payment you can
prove arrived:

1. [Accept M-Pesa payments](https://laraveldaraja.com/tutorials/stk-push/)
2. [Testing callbacks on localhost](https://laraveldaraja.com/tutorials/callbacks-localhost/)
3. [M-Pesa checkout with Livewire](https://laraveldaraja.com/tutorials/livewire-checkout/)
4. [M-Pesa checkout for a SPA or mobile app](https://laraveldaraja.com/tutorials/api-checkout/)
5. [Receive Paybill and till payments](https://laraveldaraja.com/tutorials/c2b-paybill/)
6. [Take payment by QR code](https://laraveldaraja.com/tutorials/qr-payments/)
7. [Send money to customers](https://laraveldaraja.com/tutorials/b2c-payouts/)
8. [Subscriptions and recurring payments](https://laraveldaraja.com/tutorials/subscriptions/)
9. [One app, many short codes](https://laraveldaraja.com/tutorials/multi-tenant/)

## Supported APIs

| Taking payments | Paying out | Keeping the books |
|---|---|---|
| [M-Pesa Express (STK Push)](https://laraveldaraja.com/apis/mpesa-express/) | [Business to Customer](https://laraveldaraja.com/apis/b2c/) | [Account Balance](https://laraveldaraja.com/apis/account-services/) |
| [Customer to Business](https://laraveldaraja.com/apis/c2b/) | [Business to Pochi](https://laraveldaraja.com/apis/b2c/) | [Transaction Status](https://laraveldaraja.com/apis/account-services/) |
| [Dynamic QR](https://laraveldaraja.com/apis/dynamic-qr/) | [Business to Business](https://laraveldaraja.com/apis/b2b/) | [Reversal](https://laraveldaraja.com/apis/account-services/) |
| [M-Pesa Ratiba](https://laraveldaraja.com/apis/standing-orders/) | [B2B Express Checkout](https://laraveldaraja.com/apis/b2b/) | [Pull Transactions](https://laraveldaraja.com/apis/pull-transactions/) |
| [Lipa na Bonga](https://laraveldaraja.com/apis/lipa-na-bonga/) | | [Bill Manager](https://laraveldaraja.com/apis/bill-manager/) |

Checking who you deal with: [Mobile Number Validation, SIM Swap, B2C Hakikisha
and C2B Hakikisha](https://laraveldaraja.com/apis/identity/). Safaricom enables
these per app, after a commercial agreement.

Every call and endpoint: [API overview](https://laraveldaraja.com/apis/overview/).

## Upgrading from 4.x

v5 is a rewrite. See the [upgrade guide](https://laraveldaraja.com/upgrade/v4-to-v5/). Laravel 10
and 11 are not supported; applications on those versions should stay on `^4.0`.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Help and Mizani

Questions about the package belong in the issue tracker. If you would rather
have an integration built or reviewed, the maintainer takes that on as paid
work through [Eazysoft](https://eazysoft.africa/contact).

[Mizani](https://mizani.eazysoft.africa) is the paid M-Pesa dashboard built on
this package: every payment, the callbacks that never arrived, and
reconciliation against Safaricom, in one screen. This package stays free and
MIT-licensed either way.

## Security

Report vulnerabilities to security@eazysoft.africa rather than the issue
tracker. See [SECURITY.md](SECURITY.md).

## Licence

MIT. See [LICENSE](LICENSE).
