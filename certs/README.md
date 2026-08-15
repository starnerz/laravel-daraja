# Safaricom public certificates

These certificates encrypt the initiator password into the `SecurityCredential`
that the B2C, B2B, Reversal, Account Balance and Transaction Status APIs require.
Safaricom publishes a **different certificate per environment**.

| File | Environment | Config value |
|---|---|---|
| `sandbox.cer` | `mode = sandbox` | — |
| `production.cer` | `mode = live` | — |

## Keeping them current

Download the current certificates from the Daraja portal
(<https://developer.safaricom.co.ke>) and replace the files here.

> **`production.cer` in this repository expired on 21 March 2018.**
> It is the certificate that shipped with v1 of this package. RSA encryption
> still succeeds with an expired certificate because only the public key is
> used, but it should be replaced with the current one before going live.

`sandbox.cer` is not bundled — download it from the portal. Until it exists,
`SecurityCredential` throws a `ConfigurationException` naming the missing path
rather than failing silently.

## Using your own path

To point at a certificate stored outside the package, set:

```php
// config/laravel-daraja.php
'certificate_path' => storage_path('daraja/production.cer'),
```
