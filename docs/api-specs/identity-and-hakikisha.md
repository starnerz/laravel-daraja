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

Query the last SIM swap date for a phone number:

```php
$result = Daraja::simSwap()->query('0712345678');

$result->lastSwapDate;
```

The phone is normalized to international format before sending. The service
uses `imsi/v2/checkATI`; IMSI lookup is a separate API.

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
