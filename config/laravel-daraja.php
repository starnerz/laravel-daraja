<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Mode
    |--------------------------------------------------------------------------
    |
    | Which Daraja environment to talk to: "sandbox" or "live". This selects
    | the API host and the public certificate used to encrypt the initiator
    | password into a SecurityCredential.
    |
    */

    'mode' => env('DARAJA_MODE', 'sandbox'),

    'hosts' => [
        'sandbox' => 'https://sandbox.safaricom.co.ke',
        'live' => 'https://api.safaricom.co.ke',
    ],

    /*
    |--------------------------------------------------------------------------
    | Safaricom Public Certificate
    |--------------------------------------------------------------------------
    |
    | Used to encrypt the initiator password into a SecurityCredential. Leave
    | this null to use the certificate bundled with the package for the
    | current mode, or point it at your own downloaded copy.
    |
    */

    'certificate_path' => env('DARAJA_CERTIFICATE_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Application Credentials
    |--------------------------------------------------------------------------
    |
    | The consumer key and secret of your app on the Daraja portal. These are
    | exchanged for a short-lived OAuth access token, which the package
    | caches for you.
    |
    */

    'consumer_key' => env('DARAJA_CONSUMER_KEY'),
    'consumer_secret' => env('DARAJA_CONSUMER_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Initiator
    |--------------------------------------------------------------------------
    |
    | The short code initiating transactions, plus the API operator username
    | and password. "credential" is the plaintext password; the package
    | encrypts it with Safaricom's public certificate per request.
    |
    | Supported types: paybill, till, msisdn
    |
    */

    'initiator' => [
        'name' => env('DARAJA_INITIATOR_NAME'),
        'credential' => env('DARAJA_INITIATOR_CREDENTIAL'),
        'short_code' => env('DARAJA_INITIATOR_SHORTCODE'),
        'type' => env('DARAJA_INITIATOR_TYPE', 'paybill'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Partner Name
    |--------------------------------------------------------------------------
    |
    | Your organisation's friendly name as the paying merchant knows it. Shown
    | in the B2B Express Checkout prompt: "You are about to send Ksh {amount}
    | to {partner name} for payment reference: {reference}".
    |
    */

    'partner_name' => env('DARAJA_PARTNER_NAME'),

    /*
    |--------------------------------------------------------------------------
    | Pull Transactions
    |--------------------------------------------------------------------------
    |
    | The nominated number is the MSISDN recorded against your short code's KYC
    | details on the M-Pesa organisation portal. Registration for pulling is a
    | one-time operation per short code.
    |
    */

    'pull' => [
        'nominated_number' => env('DARAJA_PULL_NOMINATED_NUMBER'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bill Manager
    |--------------------------------------------------------------------------
    |
    | The app key is returned once, in the response to the Bill Manager opt-in
    | call. Store it — bulk invoicing sends it as an "appKey" header and
    | Safaricom does not return it again.
    |
    */

    'bill_manager' => [
        'app_key' => env('DARAJA_BILL_MANAGER_APP_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | M-Pesa Express (STK Push)
    |--------------------------------------------------------------------------
    */

    'stk' => [
        'short_code' => env('DARAJA_STK_SHORTCODE'),
        'pass_key' => env('DARAJA_STK_PASS_KEY'),
        'callback_url' => env('DARAJA_STK_CALLBACK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Callback URLs
    |--------------------------------------------------------------------------
    |
    | Every value here may be either an absolute URL or the name of a route in
    | your application, which the package resolves with route(). Safaricom
    | requires publicly reachable HTTPS endpoints.
    |
    */

    'urls' => [

        'c2b' => [
            'confirmation' => env('DARAJA_C2B_CONFIRMATION_URL'),
            'validation' => env('DARAJA_C2B_VALIDATION_URL'),
        ],

        'result' => [
            'b2c' => env('DARAJA_B2C_RESULT_URL'),
            'b2b' => env('DARAJA_B2B_RESULT_URL'),
            'b2b_express' => env('DARAJA_B2B_EXPRESS_RESULT_URL'),
            'pull' => env('DARAJA_PULL_CALLBACK_URL'),
            'bill_manager' => env('DARAJA_BILL_MANAGER_CALLBACK_URL'),
            'standing_order' => env('DARAJA_STANDING_ORDER_CALLBACK_URL'),
            'reversal' => env('DARAJA_REVERSAL_RESULT_URL'),
            'balance' => env('DARAJA_BALANCE_RESULT_URL'),
            'transaction_status' => env('DARAJA_TRANSACTION_STATUS_RESULT_URL'),
        ],

        'timeout' => [
            'b2c' => env('DARAJA_B2C_TIMEOUT_URL'),
            'b2b' => env('DARAJA_B2B_TIMEOUT_URL'),
            'reversal' => env('DARAJA_REVERSAL_TIMEOUT_URL'),
            'balance' => env('DARAJA_BALANCE_TIMEOUT_URL'),
            'transaction_status' => env('DARAJA_TRANSACTION_STATUS_TIMEOUT_URL'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client
    |--------------------------------------------------------------------------
    |
    | Tuning for the underlying Laravel HTTP client. Retries apply only to
    | connection errors and 5xx responses; Daraja business failures are
    | returned as 4xx and are never retried.
    |
    */

    'http' => [
        'timeout' => (int) env('DARAJA_HTTP_TIMEOUT', 30),
        'connect_timeout' => (int) env('DARAJA_HTTP_CONNECT_TIMEOUT', 10),
        'retries' => (int) env('DARAJA_HTTP_RETRIES', 2),
        'retry_delay' => (int) env('DARAJA_HTTP_RETRY_DELAY', 250),
    ],

    /*
    |--------------------------------------------------------------------------
    | Access Token Cache
    |--------------------------------------------------------------------------
    |
    | Daraja access tokens live for one hour. They are cached slightly short of
    | that so a token is never used in the final seconds of its life. Leave
    | "store" null to use your application's default cache store.
    |
    */

    'cache' => [
        'store' => env('DARAJA_CACHE_STORE'),
        'prefix' => 'daraja',
        'token_ttl' => (int) env('DARAJA_TOKEN_TTL', 3540),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | When enabled, every Daraja request and response is logged. Credentials
    | and security credentials are redacted before anything is written.
    | Leave "channel" null to use your application's default channel.
    |
    */

    'logging' => [
        'enabled' => (bool) env('DARAJA_LOGGING_ENABLED', false),
        'channel' => env('DARAJA_LOG_CHANNEL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Routes
    |--------------------------------------------------------------------------
    |
    | Opt-in callback endpoints. When enabled the package registers routes that
    | accept Safaricom callbacks and dispatch the matching event, so you only
    | need to write listeners. Disabled by default.
    |
    */

    'routes' => [
        'enabled' => (bool) env('DARAJA_ROUTES_ENABLED', false),
        'prefix' => env('DARAJA_ROUTES_PREFIX', 'daraja'),
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Callback Security
    |--------------------------------------------------------------------------
    |
    | Safaricom callbacks carry no signature or shared secret, so the only
    | verification available is the source address. Add Safaricom's ranges here
    | — plain addresses or CIDR — and register the VerifySafaricomIp middleware
    | on the callback routes. An empty list permits every request.
    |
    | Safaricom issues these ranges to partners directly; none are bundled with
    | this package because they are not published. Treat callback contents as
    | unverified input either way and confirm value with Transaction Status
    | before releasing goods or funds.
    |
    */

    'security' => [
        'allowed_ips' => array_filter(
            explode(',', (string) env('DARAJA_ALLOWED_IPS', '')),
        ),
    ],

];
