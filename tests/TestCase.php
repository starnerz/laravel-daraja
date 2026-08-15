<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Starnerz\LaravelDaraja\LaravelDarajaServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDarajaServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('laravel-daraja.mode', 'sandbox');
        $app['config']->set('laravel-daraja.consumer_key', 'test-consumer-key');
        $app['config']->set('laravel-daraja.consumer_secret', 'test-consumer-secret');
        $app['config']->set('laravel-daraja.initiator.name', 'testapi');
        $app['config']->set('laravel-daraja.initiator.credential', 'test-credential');
        $app['config']->set('laravel-daraja.initiator.short_code', '600000');
        $app['config']->set('laravel-daraja.stk.short_code', '174379');
        $app['config']->set('laravel-daraja.stk.pass_key', 'test-pass-key');
        $app['config']->set('laravel-daraja.stk.callback_url', 'https://example.test/daraja/stk');
    }
}
