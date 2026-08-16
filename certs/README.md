# Safaricom public certificates

These certificates encrypt the initiator password into the `SecurityCredential`
required by B2C, B2B, Reversal, Account Balance and Transaction Status.
Safaricom publishes a **different certificate per environment**.

| File | Used when |
|---|---|
| `SandboxCertificate.cer` | `laravel-daraja.mode` is `sandbox` |
| `ProductionCertificate.cer` | `laravel-daraja.mode` is `live` |

Filenames match the downloads on the Daraja portal so their provenance stays
obvious. Both ship with the package and are selected automatically; consumers do
not need to download anything.

## Why they show as expired

```
SandboxCertificate.cer      notAfter  Nov 11 07:12:45 2016 GMT
ProductionCertificate.cer   notAfter  Mar 21 13:20:13 2018 GMT
```

This is expected, not a bug. These are the certificates Safaricom currently
distributes — the organisation has not published newer ones. It does not matter
in practice: `openssl_public_encrypt()` reads only the public key, and validity
dates play no part in RSA encryption. Safaricom's own systems decrypt
credentials produced with these.

Do **not** replace them with self-signed substitutes to clear the dates. Only
the matching private key on Safaricom's side can decrypt the credential.

## Maintainers: rotating a certificate

If Safaricom ever does publish new ones, download from the
[developer portal](https://developer.safaricom.co.ke), replace the file here
keeping the same name, and note the change in `CHANGELOG.md`.

Check what you have before committing it:

```bash
openssl x509 -in certs/ProductionCertificate.cer -noout -subject -dates
openssl x509 -in certs/ProductionCertificate.cer -noout -modulus | md5sum
```

Comparing the modulus is how you confirm a download actually differs from what
is already committed — the subject and issuer are identical across versions.

## Using a different certificate

An application can override the bundled files:

```php
// config/laravel-daraja.php
'certificate_path' => storage_path('daraja/production.cer'),
```

The path is checked for readability before use, so a typo raises a
`ConfigurationException` naming the path rather than a PHP warning.
