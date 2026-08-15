<?php

declare(strict_types=1);

use Starnerz\LaravelDaraja\LaravelDarajaServiceProvider;

it('registers the service provider', function () {
    expect($this->app->getProviders(LaravelDarajaServiceProvider::class))->not->toBeEmpty();
});

it('merges the package configuration', function () {
    expect(config('laravel-daraja'))->toBeArray()
        ->and(config('laravel-daraja.mode'))->toBe('sandbox');
});
