<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Starnerz\LaravelDaraja\Commands\RegisterC2BUrls;
use Starnerz\LaravelDaraja\Commands\ShowAccessToken;
use Starnerz\LaravelDaraja\Commands\ShowSecurityCredential;
use Starnerz\LaravelDaraja\Http\DarajaClient;
use Starnerz\LaravelDaraja\Http\TokenRepository;
use Starnerz\LaravelDaraja\Support\CallbackUrl;
use Starnerz\LaravelDaraja\Support\SecurityCredential;

class LaravelDarajaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-daraja')
            // Passed explicitly: hasConfigFile() would otherwise default to the
            // package's short name ("daraja"), since spatie/laravel-package-tools
            // strips the "laravel-" prefix.
            ->hasConfigFile('laravel-daraja')
            ->hasCommands([
                RegisterC2BUrls::class,
                ShowAccessToken::class,
                ShowSecurityCredential::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(CallbackUrl::class);
        $this->app->singleton(SecurityCredential::class);
        $this->app->singleton(TokenRepository::class);
        $this->app->singleton(DarajaClient::class);

        $this->app->singleton(Daraja::class, fn ($app): Daraja => new Daraja($app));
        $this->app->alias(Daraja::class, 'daraja');
    }
}
