<?php

declare(strict_types=1);

use Starnerz\LaravelDaraja\LaravelDarajaServiceProvider;

it('registers the service provider', function () {
    expect($this->app->getProviders(LaravelDarajaServiceProvider::class))->not->toBeEmpty();
});

it('merges the package configuration', function () {
    expect(config('laravel-daraja'))->toBeArray()
        ->and(config('laravel-daraja.mode'))->toBe('sandbox')
        // Regression: hasConfigFile() defaults to the short name ("daraja"),
        // which silently left every config lookup null.
        ->and(config('laravel-daraja.hosts.sandbox'))->toBe('https://sandbox.safaricom.co.ke');
});

it('leaves callback routes unregistered by default', function () {
    expect(config('laravel-daraja.routes.enabled'))->toBeFalse()
        ->and(app('router')->getRoutes()->getByName('daraja.stk'))->toBeNull();
});
