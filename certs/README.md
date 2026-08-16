# Safaricom public certificates

These certificates encrypt the initiator password into the `SecurityCredential`
required by B2C, B2B, Reversal, Account Balance and Transaction Status.
Safaricom publishes a **different certificate per environment**.

| File | Used when |
|---|---|
| `sandbox.cer` | `laravel-daraja.mode` is `sandbox` |
| `production.cer` | `laravel-daraja.mode` is `live` |

Both ship with the package and are selected automatically. Consumers of this
package do not need to download anything.

## Maintainers: rotating a certificate

When Safaricom issues a new certificate, download it from the
[developer portal](https://developer.safaricom.co.ke), replace the file here,
and note the change in `CHANGELOG.md`.

Verify what you have before committing it:

```bash
openssl x509 -in certs/production.cer -noout -subject -dates
```

> The certificate shipped with 4.x and earlier expired on 21 March 2018. RSA
> encryption still succeeds with an expired certificate because only the public
> key is used, which is why the problem went unnoticed — check `notAfter`
> rather than assuming a working credential means a current certificate.

## Using a different certificate

An application can override the bundled files:

```php
// config/laravel-daraja.php
'certificate_path' => storage_path('daraja/production.cer'),
```

The path is checked for readability before use, so a typo raises a
`ConfigurationException` naming the path rather than a PHP warning.
