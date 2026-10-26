# Identity and Hakikisha APIs

These APIs require Safaricom to enable the product for the Daraja app. Mobile
Number Validation is commercial and billed per request; B2C and C2B Hakikisha
require partner onboarding. The APIs may not work in a default sandbox app.

## Mobile Number Validation

Check in real time whether a Safaricom mobile number is registered to the
supplied identity document. The API compares the MSISDN, ID type, and ID
number, returning a match or mismatch result.

```php
use Starnerz\LaravelDaraja\Enums\MobileIdType;
use Starnerz\LaravelDaraja\Facades\Daraja;

$result = Daraja::mobileNumberValidation()->validate(
    phone: '0712345678',
    idType: MobileIdType::NationalId,
    idNumber: '12345678',
    shortCode: '776700',
    requestRefId: '83a2-4b44-aaffcb3f93',
);

if ($result->matches()) {
    // The phone number is registered to the supplied ID.
}
```

Supported ID types are `NationalId` (`01`), `MilitaryId` (`02`) and `Passport`
(`05`). The phone is normalized to international format (`254...`) before it
is sent. The shortcode defaults to `laravel-daraja.initiator.short_code`; a
unique request reference is generated if one is not supplied. The package
obtains the Daraja access token and makes the synchronous request.

### Access and onboarding

This is a commercial API. Request onboarding through
`apisupport@safaricom.co.ke` or your Safaricom account manager. Safaricom
requires a signed commercial agreement before enabling production access.

To prepare for testing, create a Daraja developer account and sandbox app, then
configure the app's consumer key and secret as described in
[Configuration](https://laraveldaraja.com/getting-started/configuration/).
The developer portal simulator provides test data. For go-live, Safaricom
requires a live M-Pesa PayBill or Till number with Business Admin/Manager
operators created.

| Environment | Endpoint |
|---|---|
| Sandbox | `https://sandbox.safaricom.co.ke/v1/KYC-validation/validateID` |
| Production | `https://api.safaricom.co.ke/v1/KYC-validation/validateID` |

The API can be consumed over the internet, a virtual private network, or a
Multiprotocol Switch, subject to Safaricom's onboarding and connectivity
requirements.

### Request and response

The request includes a unique merchant-generated reference, the organization's
shortcode, a 12-digit MSISDN, ID type, and ID number:

```json
{
  "requestRefID": "83a2-4b44-aaffcb3f93",
  "shortCode": "776700",
  "msisdn": "254710860780",
  "idType": "01",
  "idNumber": "454353453"
}
```

`Daraja::mobileNumberValidation()->validate()` generates `requestRefID` when
omitted and normalizes the phone number.

Match:

```json
{
  "responseRefID": "1771929697",
  "responseCode": "4000",
  "responseMessage": "Details match successfully",
  "status": "true"
}
```

Mismatch:

```json
{
  "responseRefID": "1771929733",
  "responseCode": "4001",
  "responseMessage": "Details do not match",
  "status": "false"
}
```

Use `$result->matches()` for the boolean outcome. The response reference and
message are available as `responseRefId` and `responseMessage`.

### Commercial charges

Safaricom lists the following monthly call rates in KES. The published
volume bands contain apparent boundary typos; confirm the applicable band
with Safaricom when agreeing commercial terms.

| Calls per month | KES per call |
|---|---:|
| 0–200,000 | 4.50 |
| 200,001–500,000 | 4.28 |
| 500,001–1,000,000 | 3.94 |
| 1,000,001–5,000,000 | 3.60 |
| More than 5,000,000 | 3.15 |

### Errors

Errors use an API-gateway response with `errorCode` and `errorMessage`, for
example:

```json
{
  "responseId": "83a2-aa8bfffcb3f939",
  "errorCode": "403.001",
  "errorMessage": "subscription_not_available"
}
```

| Code | Meaning |
|---|---|
| `403.001` | Subscription not available; the API may not be enabled for the partner |
| `400` | Bad request, such as an invalid MSISDN |
| `401.001` | Invalid access token |
| `500` | Invalid parameter input |

When Safaricom returns an `errorCode`, including in an otherwise successful
HTTP response, validation throws `ApiRequestException`. Its `errorCode`,
`getMessage()`, and `payload` preserve Safaricom's error code, message, and
response body. The gateway's `responseId` (or `requestId`, when that field is
used) is exposed as `requestId`:

```php
use Starnerz\LaravelDaraja\Exceptions\ApiRequestException;

try {
    $result = Daraja::mobileNumberValidation()->validate(
        '0712345678',
        MobileIdType::NationalId,
        '12345678',
    );
} catch (ApiRequestException $exception) {
    // Inspect $exception->errorCode and $exception->requestId.
    // Safaricom's error text is in $exception->getMessage().
}
```

`4000` and `4001` are normal validation outcomes, not API errors: they return
a response object, and `matches()` is true only for the match result.

### Audience and use cases

The API is intended for financial institutions, digital payment providers and
fintechs, digital lenders, insurers, gaming businesses, integrators, and
developers. Typical uses include digital onboarding, user authentication,
fraud prevention, and risk analysis. Treat the match result as an identity
verification signal within the partner's broader decision process.

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
