# Security Policy

## Supported versions

| Version | Supported |
|---|---|
| 5.x | ✅ |
| 4.x and earlier | ❌ |

4.x and the 2.x/3.x releases alongside it target Laravel 8 and are no longer
maintained.

## Reporting a vulnerability

Email **security@eazysoft.africa** rather than opening a public issue. Please
include the affected version, a description, and steps to reproduce.

You can expect an acknowledgement within a few days.

## Handling credentials

This package deals with payment credentials. A few things worth stating
plainly:

**Never commit credentials.** Consumer keys, secrets, initiator passwords and
passkeys belong in `.env`, which belongs in `.gitignore`.

**The initiator credential is stored as plaintext** in configuration, because
Safaricom requires it re-encrypted per request. Treat that config value with the
same care as a database password.

**Logging redacts by default.** With `DARAJA_LOGGING_ENABLED=true`, the values
of `SecurityCredential`, `Password`, `InitiatorPassword`, `consumer_key` and
`consumer_secret` are replaced with `[redacted]`. If you log Daraja payloads
yourself, redact them too.

**Callbacks are unauthenticated.** Safaricom sends no signature or shared
secret, so anyone who learns your callback URL can post to it. The
`VerifySafaricomIp` middleware restricts by source address, which is the only
verification available — and an address is spoofable. Before releasing goods or
funds, confirm the payment with the Transaction Status API rather than trusting
the callback.

**Rotate the API operator password.** Safaricom expires it every 90 days.

## Known operational risks

- The bundled certificates show expiry dates in 2016 and 2018. These are the
  files Safaricom distributes and they work as intended — RSA encryption uses
  only the public key. Do not substitute a self-signed certificate to clear the
  dates; only Safaricom holds the matching private key.
- C2B URL registration in production is one-time. Deleting and re-registering
  requires portal access, so treat a wrong URL as an incident.
