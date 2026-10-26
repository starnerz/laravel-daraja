<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Starnerz\LaravelDaraja\Daraja;
use Starnerz\LaravelDaraja\Http\C2BHakikishaHandler;

class C2BHakikishaController
{
    public function token(Request $request, C2BHakikishaHandler $handler): JsonResponse
    {
        [$payload, $status] = $handler->issueToken(
            $request->header('Authorization'),
            $request->query('grant_type'),
        );

        return response()->json($payload, $status);
    }

    public function validate(
        Request $request,
        Daraja $daraja,
        C2BHakikishaHandler $handler,
    ): JsonResponse {
        [$payload, $status] = $handler->validate(
            $request->header('Authorization'),
            $request->all(),
            $daraja->c2bAccountResolver(),
        );

        return response()->json($payload, $status);
    }
}
