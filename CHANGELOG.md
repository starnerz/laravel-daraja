# Changelog

All notable changes to this project are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and
this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] — unreleased

A full rewrite. See the
[upgrade guide](https://starnerz.github.io/daraja-docs/upgrade/v1-to-v2/).

### Changed — breaking

- **Requires PHP 8.3 and Laravel 12 or 13.** Laravel 10 and 11 are excluded:
  both are past end of life and carry unpatched advisories, so Composer refuses
  to install them without disabling its security audit.
- **The facade is now `Daraja`.** `MpesaApi` and the `Requests\*` classes are
  replaced by the `Apis\*` namespace, resolved through the container.
- **Responses are readonly objects** rather than `stdClass`. The decoded array
  remains available on `->raw`.
- **`ApiRequestException` replaces `MpesaApiRequestException`**, and now carries
  `errorCode`, `requestId`, `status` and `payload`.
- **Configuration keys reorganised** — `stk_push.*` to `stk.*`, `c2b_url.*` to
  `urls.c2b.*`, `result_url.*` to `urls.result.*`, `logs.*` to `logging.*`.
- **`push()` argument order changed**: the account reference now precedes the
  description.
- **C2B endpoints move to v2** and **B2C to v3**. C2B v2 masks the MSISDN where
  v1 sent a SHA-256 hash, and B2C v3 requires a unique
  `OriginatorConversationID`, which the package generates.
- **Certificates are per environment**, at `certs/sandbox.cer` and
  `certs/production.cer`, or via `DARAJA_CERTIFICATE_PATH`. The single bundled
  certificate in v1 expired in March 2018.

### Added

- Dynamic QR, M-Pesa Ratiba, Bill Manager, Pull Transactions, Lipa na Bonga,
  B2B Express Checkout, B2C Account Top Up and Business to Pochi.
- Opt-in callback routes that dispatch typed events, with parsers that handle
  Daraja's shape changes between success and failure.
- `Daraja::validateC2BUsing()` for the synchronous C2B validation decision.
- `VerifySafaricomIp` middleware for restricting callbacks by source address.
- `AccountBalances`, which unpacks the delimited balance string into named
  accounts.
- `daraja:token` and `daraja:credential` commands.
- Enums for command IDs, identifier types, transaction types, QR types and
  standing order frequencies.

### Fixed

- **`STK::push()` was uncallable.** A required parameter followed an optional
  one, so the signature in v1's own README raised an `ArgumentCountError`.
- **`str_limit()` was removed in Laravel 6**, breaking every STK push.
- **`daraja:register-urls` read the wrong config namespace** (`mpesaapi.*`
  rather than `laravel-daraja.*`), so it always sent empty URLs.
- **An access token was fetched on every instantiation.** Tokens are now cached
  for just under their hour-long lifetime.
- **TLS verification was disabled in sandbox mode.**
- **`monolog/monolog ^2` conflicted with Laravel 11+.** The package now logs
  through Laravel's logger.
- Configuration problems raise a `ConfigurationException` naming the key or path
  instead of surfacing as a PHP warning promoted to `ErrorException`.

## [1.0.0]

Initial releases supporting M-Pesa Express, C2B, B2C, B2B, Account Balance,
Transaction Status and Reversal on Laravel 5.5+.

[2.0.0]: https://github.com/starnerz/laravel-daraja/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/starnerz/laravel-daraja/releases/tag/v1.0.0
