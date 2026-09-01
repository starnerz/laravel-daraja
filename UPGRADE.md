# Upgrading

## 4.x to 5.0

Full guide with examples:
**https://laraveldaraja.com/upgrade/v4-to-v5/**

Versions 2.0.0, 3.0.0 and 4.0.0 were released on the same day in 2020 and share
the 1.x codebase, tracking successive Laravel majors. Whichever of those you are
on, this guide applies.

### Requirements

PHP 8.3+ and Laravel 12 or 13, up from PHP 7.3 and Laravel 8. Laravel 10 and 11
are not supported — stay on `^4.0` if you cannot upgrade.

### Facade and classes

```diff
- use Starnerz\LaravelDaraja\Facades\MpesaApi;
+ use Starnerz\LaravelDaraja\Facades\Daraja;
```

| 4.x | 5.0 |
|---|---|
| `MpesaApi::STK()->push($phone, $amount, $desc, $ref)` | `Daraja::stk()->push($phone, $amount, $ref, $desc)` |
| `MpesaApi::STK()->transactionStatus($id)` | `Daraja::stk()->query($id)` |
| `MpesaApi::c2b()->simulatePaymentToPaybill(…)` | `Daraja::c2b()->simulatePayBill(…)` |
| `MpesaApi::c2b()->simulatePaymentToTill(…)` | `Daraja::c2b()->simulateBuyGoods(…)` |
| `MpesaApi::b2c()->…` | `Daraja::b2c()->business()` / `->salary()` / `->promotion()` |
| `MpesaApi::balance()->…` | `Daraja::balance()->query()` |
| `MpesaApi::transaction()->…` | `Daraja::transaction()->query()` |
| `MpesaApi::reversal()->…` | `Daraja::reversal()->reverse()` |

`push()` takes the account reference **before** the description.

### Configuration

```bash
php artisan vendor:publish --tag=laravel-daraja-config --force
```

| 4.x | 5.0 |
|---|---|
| `stk_push.*` | `stk.*` |
| `c2b_url.*` | `urls.c2b.*` |
| `result_url.*` | `urls.result.*` |
| `queue_timeout_url.*` | `urls.timeout.*` |
| `logs.enabled` | `logging.enabled` |
| `logs.level` | `logging.channel` |

`mode` now reads from `DARAJA_MODE` instead of being hardcoded.

### Responses

```diff
- $response->CheckoutRequestID;
+ $response->checkoutRequestId;
+ $response->accepted();
```

The decoded array is still on `$response->raw`.

### Exceptions

```diff
- use Starnerz\LaravelDaraja\Exceptions\MpesaApiRequestException;
+ use Starnerz\LaravelDaraja\Exceptions\ApiRequestException;
```

### Endpoints

C2B moved to v2 and B2C to v3.

- **C2B v2 masks the MSISDN** (`2547 ***** 126`); the older endpoint sent a
  SHA-256 hash. Code matching customers on that value needs revisiting.
- **B2C v3 requires `OriginatorConversationID`.** One is generated per request;
  pass your own to make retries idempotent.

### Certificates

4.x bundled only the production certificate and used it for sandbox too. v5
ships both and selects by `mode`, so nothing is required of you. To use your own
copy instead, set `DARAJA_CERTIFICATE_PATH`.
