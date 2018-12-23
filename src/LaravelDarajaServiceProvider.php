<?php

namespace Starnerz\LaravelDaraja;

use Starnerz\LaravelDaraja\Commands\RegisterC2BUrls;

class LaravelDarajaServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Package path to config.
     */
    const CONFIG_PATH = __DIR__.'/../config/laravel-daraja.php';

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            self::CONFIG_PATH => config_path('laravel-daraja.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                RegisterC2BUrls::class,
            ]);
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'laravel-daraja');

        $this->app->bind('mpesa-api', function () {
            return new MpesaApi();
        });
    }
}
