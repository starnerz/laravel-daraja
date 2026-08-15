<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Starnerz\LaravelDaraja\Daraja;
use Starnerz\LaravelDaraja\Data\Callbacks\C2BTransaction;
use Starnerz\LaravelDaraja\Data\Callbacks\ResultCallback;
use Starnerz\LaravelDaraja\Data\Callbacks\StkCallback;
use Starnerz\LaravelDaraja\Events\C2BPaymentReceived;
use Starnerz\LaravelDaraja\Events\C2BValidationRequested;
use Starnerz\LaravelDaraja\Events\ResultReceived;
use Starnerz\LaravelDaraja\Events\StkCallbackReceived;
use Starnerz\LaravelDaraja\Events\TimeoutReceived;

/**
 * Optional endpoints that accept Safaricom callbacks and turn them into events.
 *
 * Every action answers 200 with an acknowledgement, because Safaricom retries
 * or flags endpoints that error. Application failures belong in listeners, not
 * in the response to Safaricom.
 */
class CallbackController
{
    public function stk(Request $request): JsonResponse
    {
        StkCallbackReceived::dispatch(StkCallback::fromArray($request->all()));

        return $this->acknowledge();
    }

    /**
     * Safaricom asks whether to accept a payment and waits roughly 8 seconds.
     */
    public function validation(Request $request, Daraja $daraja): JsonResponse
    {
        $transaction = C2BTransaction::fromArray($request->all());

        C2BValidationRequested::dispatch($transaction);

        $decision = ($daraja->c2bValidator() ?? fn (): bool => true)($transaction);

        if ($decision === true) {
            return $this->acknowledge('Accepted');
        }

        return response()->json([
            // A string code rejects with a specific reason; false is a generic
            // rejection. Anything other than "0" rejects the payment.
            'ResultCode' => is_string($decision) ? $decision : 'C2B00016',
            'ResultDesc' => 'Rejected',
        ]);
    }

    public function confirmation(Request $request): JsonResponse
    {
        C2BPaymentReceived::dispatch(C2BTransaction::fromArray($request->all()));

        return $this->acknowledge();
    }

    public function result(Request $request, string $type): JsonResponse
    {
        ResultReceived::dispatch($type, ResultCallback::fromArray($request->all()));

        return $this->acknowledge();
    }

    public function timeout(Request $request, string $type): JsonResponse
    {
        TimeoutReceived::dispatch($type, $request->all());

        return $this->acknowledge();
    }

    private function acknowledge(string $description = 'Accepted'): JsonResponse
    {
        return response()->json([
            'ResultCode' => '0',
            'ResultDesc' => $description,
        ]);
    }
}
