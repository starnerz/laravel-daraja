# Identity and Hakikisha APIs

These APIs require Safaricom to enable the product for the Daraja app. Mobile
Number Validation is commercial and billed per request; B2C and C2B Hakikisha
require partner onboarding. The APIs may not work in a default sandbox app.

## Mobile Number Validation

Check whether a Safaricom number is registered to an identity document:

```php
use Starnerz\LaravelDaraja\Enums\MobileIdType;
use Starnerz\LaravelDaraja\Facades\Daraja;

$result = Daraja::mobileNumberValidation()->validate(
    phone: '0712345678',
    idType: MobileIdType::NationalId,
    idNumber: '12345678',
);

if ($result->matches()) {
    // The phone number is registered to the supplied ID.
}
```

Supported ID types are `NationalId` (`01`), `MilitaryId` (`02`) and `Passport`
(`05`). The shortcode defaults to `laravel-daraja.initiator.short_code`. A
request reference is generated if one is not supplied.

## SIM Swap

Check when a Safaricom SIM was most recently swapped. Safaricom only reports
swaps from the previous three months; older swaps are represented by
`01-01-1900 00:00`.

```php
$result = Daraja::simSwap()->query('0712345678');

if ($result->successful()) {
    $result->lastSwapDate;
}
```

The package obtains and sends the Daraja access token automatically. It
normalizes the phone to international format and posts `customerNumber` to
`imsi/v2/checkATI`.

### Access and onboarding

This is a commercial API and requires a signed commercial agreement and
Safaricom enablement. Request access through `apisupport@safaricom.co.ke` or
your Safaricom account manager. Once the agreement is complete, Safaricom
enables the partner in sandbox and production.

Before testing, create a Daraja developer account and sandbox app, then
configure that app's consumer key and secret as described in
[Configuration](https://laraveldaraja.com/getting-started/configuration/).
Sandbox simulator test data is available in the developer portal. The API is
available over the internet.

| Environment | Endpoint |
|---|---|
| Sandbox | `https://sandbox.safaricom.co.ke/imsi/v2/checkATI` |
| Production | `https://api.safaricom.co.ke/imsi/v2/checkATI` |

### Request and response

The request body contains the customer's MSISDN in 12-digit international
format:

```json
{
  "customerNumber": "254722000000"
}
```

`Daraja::simSwap()->query()` accepts a locally formatted number as well; the
package normalizes it before sending.

```json
{
  "requestRefID": "4277-415525-1",
  "responseCode": "200",
  "responseDesc": "Success",
  "lastSwapDate": "01-01-1900 00:00"
}
```

`requestRefID` identifies the request. `responseCode` and `responseDesc`
describe its processing status; Safaricom documents `200` as a successful
submission. `lastSwapDate` is the most recent swap date, or the default date
when no swap is available within the three-month lookup window.

The API is described as asynchronous by Safaricom. Treat the response status
as the submission outcome and follow the partner's enabled integration
arrangement for any subsequent processing.

### Commercial charges

Monthly successful calls are charged by volume. Prices below are in KES per
call, exclusive of VAT; unsuccessful calls are not charged.

| Calls per month | KES per call |
|---|---:|
| 0–200,000 | 1.20 |
| 200,000–500,000 | 0.90 |
| 500,000–1,000,000 | 0.70 |
| 1,000,000–5,000,000 | 0.60 |
| More than 5,000,000 | 0.50 |

### Common HTTP errors

| Status | Meaning |
|---|---|
| 400 | Invalid request |
| 401 | Authentication required or invalid |
| 403 | The app is not permitted to use the resource |
| 404 | Resource not found |
| 405 | HTTP method not supported |
| 408, 504 | Request or upstream timeout |
| 429 | Rate limit exceeded |
| 500, 501, 502, 503 | Server, gateway, or availability failure |

### Typical uses

Partners use recent-swap checks as one input to risk controls: reviewing
high-risk banking activity, strengthening mobile-banking onboarding,
screening cheque clearance and high-value payouts, pausing password resets
after a recent swap, and supporting fraud or chargeback investigations. A swap
result should be combined with the partner's own risk policy rather than used
as the sole decision signal.

IMSI lookup is a separate API from this SIM-swap check.

## B2C Hakikisha

Verify the masked registered name for a number before sending a B2C payout:

```php
$customer = Daraja::b2cHakikisha()->validate('0712345678');

if ($customer->successful()) {
    $customer->firstName;
    $customer->middleName;
    $customer->lastName;
}
```

The API request includes a generated request ID and Unix timestamp. The
configured initiator shortcode is used unless overridden.

## C2B Hakikisha

C2B Hakikisha is receiver-side: Safaricom calls your app to obtain a token and
resolve an account name. Enable package routes and configure the partner
credentials:

```dotenv
DARAJA_ROUTES_ENABLED=true
DARAJA_C2B_HAKIKISHA_USERNAME=partner-user
DARAJA_C2B_HAKIKISHA_PASSWORD=change-this-secret
DARAJA_C2B_HAKIKISHA_TOKEN_TTL=3599
```

Register an account resolver in an application service provider:

```php
use Starnerz\LaravelDaraja\Facades\Daraja;

Daraja::resolveC2BAccountUsing(
    fn (string $accountNumber, string $shortcode): ?string =>
        Account::where('shortcode', $shortcode)
            ->where('number', $accountNumber)
            ->value('name'),
);
```

With the default `daraja` route prefix, configure Safaricom to call:

| Purpose | Method and path |
|---|---|
| Token | `POST /daraja/c2b-hakikisha/token?grant_type=client_credentials` |
| Account validation | `POST /daraja/c2b-hakikisha/validation` |

The token endpoint expects HTTP Basic authentication using the configured
username and password. The validation endpoint expects that returned token as a
Bearer token. The resolver receives the account number and shortcode and
returns the account name, or `null` if the account is not found. Use a shared
Laravel cache store when running multiple application instances.
