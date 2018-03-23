<?php

namespace Starnerz\LaravelDaraja\Tests;

use Orchestra\Testbench\TestCase;
use Starnerz\LaravelDaraja\LaravelDarajaServiceProvider;

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
