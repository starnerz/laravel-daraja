<?php

namespace Starnerz\LaravelDaraja\Tests;

use Starnerz\LaravelDaraja\Facades\LaravelDaraja;
use Starnerz\LaravelDaraja\LaravelDarajaServiceProvider;
use Orchestra\Testbench\TestCase;

class LaravelDarajaTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [LaravelDarajaServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'laravel-daraja' => LaravelDarajaServiceProvider::class,
        ];
    }

    public function testExample()
    {
        assertEquals(1, 1);
    }
}
