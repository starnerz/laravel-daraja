<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Starnerz\LaravelDaraja\Http\Controllers\C2BHakikishaController;
use Starnerz\LaravelDaraja\Http\Controllers\CallbackController;

/*
|--------------------------------------------------------------------------
| Daraja Callback Routes
|--------------------------------------------------------------------------
|
| Registered only when laravel-daraja.routes.enabled is true. The prefix and
| middleware come from the same config block.
|
| Every route is POST — Safaricom never uses another verb for callbacks.
|
*/

Route::post('stk', [CallbackController::class, 'stk'])
    ->name('daraja.stk');

Route::post('c2b/validation', [CallbackController::class, 'validation'])
    ->name('daraja.c2b.validation');

Route::post('c2b/confirmation', [CallbackController::class, 'confirmation'])
    ->name('daraja.c2b.confirmation');

Route::post('c2b-hakikisha/token', [C2BHakikishaController::class, 'token'])
    ->name('daraja.c2b-hakikisha.token');

Route::post('c2b-hakikisha/validation', [C2BHakikishaController::class, 'validation'])
    ->name('daraja.c2b-hakikisha.validation');

// {type} is echoed back on the event so one listener can serve every API.
Route::post('result/{type}', [CallbackController::class, 'result'])
    // Digits matter here: the types include b2c, b2b and c2b.
    ->where('type', '[a-z0-9-]+')
    ->name('daraja.result');

Route::post('timeout/{type}', [CallbackController::class, 'timeout'])
    // Digits matter here: the types include b2c, b2b and c2b.
    ->where('type', '[a-z0-9-]+')
    ->name('daraja.timeout');
