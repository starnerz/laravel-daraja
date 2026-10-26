<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Starnerz\LaravelDaraja\Http\C2BHakikishaHandler;

/**
 * The two endpoints Safaricom calls for C2B Hakikisha: a token, then an
 * account-name lookup made with it.
 */
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

    public function validation(Request $request, C2BHakikishaHandler $handler): JsonResponse
    {
        [$payload, $status] = $handler->validate(
            $request->header('Authorization'),
            $request->all(),
        );

        return response()->json($payload, $status);
    }
}
