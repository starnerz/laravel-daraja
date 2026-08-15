<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Tests;

/**
 * Callback routes are registered while the package boots, so enabling them has
 * to happen before providers run — a config change inside a test is too late.
 */
abstract class RoutingTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('laravel-daraja.routes.enabled', true);
    }
}
