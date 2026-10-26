<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Starnerz\LaravelDaraja\Data\Callbacks\C2BTransaction;
use Starnerz\LaravelDaraja\Events\C2BPaymentReceived;
use Starnerz\LaravelDaraja\Events\C2BValidationRequested;
use Starnerz\LaravelDaraja\Events\ResultReceived;
use Starnerz\LaravelDaraja\Events\StkCallbackReceived;
use Starnerz\LaravelDaraja\Events\TimeoutReceived;
use Starnerz\LaravelDaraja\Exceptions\ConfigurationException;
use Starnerz\LaravelDaraja\Facades\Daraja;

beforeEach(function () {
    Event::fake();
    config()->set('laravel-daraja.c2b_hakikisha.username', 'partner');
    config()->set('laravel-daraja.c2b_hakikisha.password', 'secret');
});

it('turns an STK callback into an event', function () {
    $this->postJson('daraja/stk', payload('stk-callback-success'))
        ->assertOk()
        ->assertJson(['ResultCode' => '0']);

    Event::assertDispatched(StkCallbackReceived::class, function (StkCallbackReceived $event): bool {
        expect($event->callback->receipt())->toBe('NLJ7RT61SV')
            ->and($event->callback->successful())->toBeTrue();

        return true;
    });
});

it('acknowledges a C2B confirmation and dispatches the payment', function () {
    $this->postJson('daraja/c2b/confirmation', payload('c2b-confirmation'))
        ->assertOk()
        ->assertJson(['ResultCode' => '0', 'ResultDesc' => 'Accepted']);

    Event::assertDispatched(C2BPaymentReceived::class, function (C2BPaymentReceived $event): bool {
        expect($event->transaction->transactionId)->toBe('RKL51ZDR4F');

        return true;
    });
});

it('accepts a validation request by default', function () {
    $this->postJson('daraja/c2b/validation', payload('c2b-confirmation'))
        ->assertOk()
        ->assertJson(['ResultCode' => '0', 'ResultDesc' => 'Accepted']);

    Event::assertDispatched(C2BValidationRequested::class);
});

it('rejects a validation request with a specific Safaricom code', function () {
    Daraja::validateC2BUsing(fn (C2BTransaction $t): bool|string => 'C2B00012');

    $this->postJson('daraja/c2b/validation', payload('c2b-confirmation'))
        ->assertOk()
        ->assertJson(['ResultCode' => 'C2B00012', 'ResultDesc' => 'Rejected']);
});

it('rejects generically when the validator returns false', function () {
    Daraja::validateC2BUsing(fn (C2BTransaction $t): bool => false);

    $this->postJson('daraja/c2b/validation', payload('c2b-confirmation'))
        ->assertOk()
        ->assertJson(['ResultCode' => 'C2B00016']);
});

it('passes the transaction to the validator', function () {
    $seen = null;

    Daraja::validateC2BUsing(function (C2BTransaction $t) use (&$seen): bool {
        $seen = $t->billReferenceNumber;

        return true;
    });

    $this->postJson('daraja/c2b/validation', payload('c2b-confirmation'))->assertOk();

    expect($seen)->toBe('Sample Transaction');
});

it('routes a result callback and tags it with its type', function () {
    $this->postJson('daraja/result/b2c', payload('b2c-result-success'))->assertOk();

    Event::assertDispatched(ResultReceived::class, function (ResultReceived $event): bool {
        expect($event->type)->toBe('b2c')
            ->and($event->result->receipt())->toBe('SG632NMUAB');

        return true;
    });
});

it('routes a timeout callback', function () {
    $this->postJson('daraja/timeout/balance', ['Result' => ['ResultCode' => 1]])->assertOk();

    Event::assertDispatched(TimeoutReceived::class, fn (TimeoutReceived $e): bool => $e->type === 'balance');
});

it('still acknowledges a failed result so Safaricom stops retrying', function () {
    $this->postJson('daraja/result/b2c', payload('b2c-result-failure'))
        ->assertOk()
        ->assertJson(['ResultCode' => '0']);

    Event::assertDispatched(
        ResultReceived::class,
        fn (ResultReceived $e): bool => $e->result->successful() === false,
    );
});

it('rejects a callback from an address outside the allow list', function () {
    config()->set('laravel-daraja.security.allowed_ips', ['196.201.214.0/24']);

    $this->withMiddleware(Starnerz\LaravelDaraja\Http\Middleware\VerifySafaricomIp::class);

    $middleware = new Starnerz\LaravelDaraja\Http\Middleware\VerifySafaricomIp;
    $request = Illuminate\Http\Request::create('/daraja/stk', 'POST');
    $request->server->set('REMOTE_ADDR', '203.0.113.9');

    expect(fn () => $middleware->handle($request, fn ($r) => response('ok')))
        ->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('allows a callback from an address inside the allow list', function () {
    config()->set('laravel-daraja.security.allowed_ips', ['196.201.214.0/24']);

    $middleware = new Starnerz\LaravelDaraja\Http\Middleware\VerifySafaricomIp;
    $request = Illuminate\Http\Request::create('/daraja/stk', 'POST');
    $request->server->set('REMOTE_ADDR', '196.201.214.200');

    expect($middleware->handle($request, fn ($r) => response('ok'))->getContent())->toBe('ok');
});

it('allows every address when no allow list is configured', function () {
    config()->set('laravel-daraja.security.allowed_ips', []);

    $middleware = new Starnerz\LaravelDaraja\Http\Middleware\VerifySafaricomIp;
    $request = Illuminate\Http\Request::create('/daraja/stk', 'POST');
    $request->server->set('REMOTE_ADDR', '203.0.113.9');

    expect($middleware->handle($request, fn ($r) => response('ok'))->getContent())->toBe('ok');
});

it('issues a C2B Hakikisha token to a configured partner', function () {
    $this->withHeader('Authorization', 'Basic '.base64_encode('partner:secret'))
        ->postJson('daraja/c2b-hakikisha/token?grant_type=client_credentials')
        ->assertOk()
        ->assertJsonStructure(['access_token', 'expires_in']);
});

it('rejects invalid C2B Hakikisha client credentials', function () {
    $this->withHeader('Authorization', 'Basic '.base64_encode('partner:wrong'))
        ->postJson('daraja/c2b-hakikisha/token?grant_type=client_credentials')
        ->assertUnauthorized()
        ->assertJson(['error' => 'unauthorized']);
});

it('resolves a C2B Hakikisha account name for an authenticated request', function () {
    Daraja::resolveC2BAccountUsing(
        fn (string $accountNumber, string $shortcode): ?string => $accountNumber === '66925336'
            && $shortcode === '415010'
            ? 'Money Market Account'
            : null,
    );

    $tokenResponse = $this->withHeader('Authorization', 'Basic '.base64_encode('partner:secret'))
        ->postJson('daraja/c2b-hakikisha/token?grant_type=client_credentials')
        ->assertOk();

    $this->withHeader('Authorization', 'Bearer '.$tokenResponse->json('access_token'))
        ->postJson('daraja/c2b-hakikisha/validation', [
            'requestId' => 'request-1',
            'timestamp' => '1793000000',
            'accountNumber' => '66925336',
            'shortcode' => '415010',
        ])
        ->assertOk()
        ->assertJson([
            'requestId' => 'request-1',
            'accountName' => 'Money Market Account',
            'accountNumber' => '66925336',
            'shortcode' => '415010',
        ]);
});

it('rejects an invalid C2B Hakikisha access token', function () {
    $this->withHeader('Authorization', 'Bearer invalid')
        ->postJson('daraja/c2b-hakikisha/validation', [
            'requestId' => 'request-1',
            'timestamp' => '1793000000',
            'accountNumber' => '66925336',
            'shortcode' => '415010',
        ])
        ->assertUnauthorized()
        ->assertJson(['errorMessage' => 'Invalid access token.']);
});

it('rejects malformed C2B Hakikisha validation requests', function () {
    $token = $this->withHeader('Authorization', 'Basic '.base64_encode('partner:secret'))
        ->postJson('daraja/c2b-hakikisha/token?grant_type=client_credentials')
        ->json('access_token');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('daraja/c2b-hakikisha/validation', ['requestId' => 'request-1'])
        ->assertStatus(422)
        ->assertJson(['requestId' => 'request-1']);
});

it('checks the C2B Hakikisha token before the request body', function () {
    $this->withHeader('Authorization', 'Bearer any')
        ->postJson('daraja/c2b-hakikisha/validation', ['requestId' => 'request-1'])
        ->assertUnauthorized();
});

it('refuses a C2B Hakikisha token for any other grant type', function () {
    $this->withHeader('Authorization', 'Basic '.base64_encode('partner:secret'))
        ->postJson('daraja/c2b-hakikisha/token?grant_type=password')
        ->assertStatus(400)
        ->assertJson(['error' => 'unsupported_grant_type']);
});

it('answers an unknown account with 400', function () {
    Daraja::resolveC2BAccountUsing(fn (): ?string => null);

    $token = $this->withHeader('Authorization', 'Basic '.base64_encode('partner:secret'))
        ->postJson('daraja/c2b-hakikisha/token?grant_type=client_credentials')
        ->json('access_token');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('daraja/c2b-hakikisha/validation', [
            'requestId' => 'request-1',
            'timestamp' => '1793000000',
            'accountNumber' => 'nope',
            'shortcode' => '415010',
        ])
        ->assertStatus(400)
        ->assertJson(['errorMessage' => 'Invalid account number.']);
});

it('says how to register a resolver when none is registered', function () {
    $this->withoutExceptionHandling();

    $token = $this->withHeader('Authorization', 'Basic '.base64_encode('partner:secret'))
        ->postJson('daraja/c2b-hakikisha/token?grant_type=client_credentials')
        ->json('access_token');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('daraja/c2b-hakikisha/validation', [
            'requestId' => 'request-1',
            'timestamp' => '1793000000',
            'accountNumber' => '66925336',
            'shortcode' => '415010',
        ]);
})->throws(ConfigurationException::class, 'Daraja::resolveC2BAccountUsing()');
