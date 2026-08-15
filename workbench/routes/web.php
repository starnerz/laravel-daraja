<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Workbench Routes
|--------------------------------------------------------------------------
|
| Callback sinks for manual sandbox testing. Point an ngrok tunnel at the
| Workbench app (or at the daraja.app Homestead site) and register these
| as your Daraja callback URLs to watch real Safaricom payloads arrive.
|
*/

Route::get('/', fn () => ['package' => 'starnerz/laravel-daraja', 'mode' => config('laravel-daraja.mode')]);

Route::post('/daraja/{callback}', function (Request $request, string $callback) {
    Log::channel(config('laravel-daraja.logging.channel') ?? config('logging.default'))
        ->info("Daraja callback [{$callback}] received", $request->all());

    return response()->json([
        'ResultCode' => 0,
        'ResultDesc' => 'Accepted',
    ]);
})->where('callback', '[A-Za-z0-9\-]+')->name('daraja.callback');
