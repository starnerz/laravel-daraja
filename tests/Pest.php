<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Starnerz\LaravelDaraja\Tests\RoutingTestCase;
use Starnerz\LaravelDaraja\Tests\TestCase;

uses(TestCase::class)->in(__DIR__.'/Feature', __DIR__.'/Unit');

// Callback routes register during boot, so they need an application configured
// before providers run — hence a separate base case and directory.
uses(RoutingTestCase::class)->in(__DIR__.'/Routing');

/**
 * Fake the Daraja endpoints a test needs.
 *
 * Overrides are listed first because Http::fake() merges successive calls and
 * the first matching stub wins — a later fake() cannot replace an earlier one,
 * so every test registers its stubs exactly once through here.
 *
 * @param  array<string, mixed>  $overrides
 */
function fakeDaraja(array $overrides = []): void
{
    Http::fake($overrides + [
        '*/oauth/*' => Http::response(payload('oauth-token')),
        '*/mpesa/stkpush/*' => Http::response(payload('stk-push')),
        '*/mpesa/stkpushquery/*' => Http::response(payload('stk-query')),
        '*/mpesa/c2b/*/registerurl' => Http::response(payload('c2b-register')),
        '*/mpesa/c2b/*/simulate' => Http::response(payload('acknowledgement')),
        '*/mpesa/qrcode/*' => Http::response(payload('dynamic-qr')),
        '*/standingorder/*' => Http::response(payload('standing-order')),
        '*/v1/ussdpush/*' => Http::response(['code' => '0', 'status' => 'USSD Initiated Successfully']),
        '*/v1/billmanager-invoice/optin' => Http::response(payload('bill-manager-optin')),
        '*/v1/billmanager-invoice/*' => Http::response(['Status_Message' => 'Invoice sent successfully', 'resmsg' => 'Success', 'rescode' => '200']),
        '*/pulltransactions/v1/register' => Http::response(['ResponseRefID' => 'feb5e3f2', 'ResponseStatus' => '1000', 'ShortCode' => '600000', 'ResponseDescription' => 'Shortcode Registered Successfully']),
        '*/pulltransactions/v1/query' => Http::response(payload('pull-transactions')),
        '*/v1/lipa/na/bonga/*' => Http::response(payload('bonga-calculate')),
        // Everything else on the Daraja hosts acknowledges generically.
        '*safaricom.co.ke/*' => Http::response(payload('acknowledgement')),
    ]);
}

/**
 * Load a recorded Daraja response fixture.
 *
 * Named payload() rather than fixture() because Pest 4 defines its own
 * fixture() helper.
 *
 * @return array<string, mixed>
 */
function payload(string $name): array
{
    $path = __DIR__.'/Fixtures/'.$name.'.json';

    if (! file_exists($path)) {
        throw new InvalidArgumentException("Fixture [{$name}] does not exist at [{$path}].");
    }

    return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
}
