# Changelog

All notable changes to this project are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and
this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [5.2.0] — 2026-09-01

### Added

- **Guzzle 8 support.** The requirement is now `^7.8 || ^8.0`. Nothing in the
  package changes: this widens what it permits, so an application that has
  already moved to Guzzle 8 can install it at all. Applications on Guzzle 7 are
  unaffected and need do nothing.
  Both are covered by CI rather than assumed — Laravel 12 resolves Guzzle 7 and
  Laravel 13 resolves Guzzle 8, on PHP 8.3 and 8.4 alike.

### Changed

- **Vulnerability reports now go to `security@eazysoft.africa`** rather than a
  personal address. `SECURITY.md` is the authority; the README and
  `CONTRIBUTING.md` agree with it.
- Documentation moved to **https://laraveldaraja.com/**. The old
  `starnerz.github.io/daraja-docs/` URLs redirect, so existing links keep
  working.

## [5.1.0] — 2026-09-01

### Added

- **`DarajaRequestSending` and `DarajaResponseReceived` events**, dispatched
  around every outbound call made through `DarajaClient`. Between these and the
  five callback events, both directions of a Daraja integration are now
  observable without decorating anything.
  The request payload and the response body carry the same redaction the
  logger applies, so a listener may persist them safely. A call that never
  received a response dispatches only the first event, which is how a listener
  tells an unanswered request from a rejected one.

## [5.0.0] — 2026-08-18

A full rewrite. See the
[upgrade guide](https://laraveldaraja.com/upgrade/v4-to-v5/).

### Changed — breaking

- **Requires PHP 8.3 and Laravel 12 or 13**, up from PHP 7.3 and Laravel 8.
  Laravel 10 and 11 are excluded:
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
- **Certificates are per environment.** The package now ships both Safaricom
  certificates and picks the one matching `mode`, where earlier versions bundled
  only the production one and used it for sandbox too.

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

## [4.0.0] — 2020-09-10

Laravel 8 support. Together with 3.0.0 and 2.0.0, released the same day, this
tracked successive Laravel majors over the 1.x codebase.

## [1.0.0] — 2018-03-23

Initial releases supporting M-Pesa Express, C2B, B2C, B2B, Account Balance,
Transaction Status and Reversal on Laravel 5.5+.

[5.2.0]: https://github.com/starnerz/laravel-daraja/compare/v5.1.0...v5.2.0
[5.1.0]: https://github.com/starnerz/laravel-daraja/compare/v5.0.0...v5.1.0
[5.0.0]: https://github.com/starnerz/laravel-daraja/compare/v4.0.0...v5.0.0
[4.0.0]: https://github.com/starnerz/laravel-daraja/compare/v1.0.4...v4.0.0
[1.0.0]: https://github.com/starnerz/laravel-daraja/releases/tag/v1.0.0
