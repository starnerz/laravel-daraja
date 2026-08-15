<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Commands;

use Illuminate\Console\Command;
use Starnerz\LaravelDaraja\Daraja;
use Starnerz\LaravelDaraja\Exceptions\DarajaException;

class ShowAccessToken extends Command
{
    protected $signature = 'daraja:token {--fresh : Discard the cached token first}';

    protected $description = 'Fetch a Daraja OAuth access token to verify your credentials';

    public function handle(Daraja $daraja): int
    {
        if ($this->option('fresh')) {
            $daraja->forgetAccessToken();
        }

        try {
            $token = $daraja->accessToken();
        } catch (DarajaException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Mode', (string) config('laravel-daraja.mode'));
        $this->components->twoColumnDetail('Access token', $token);

        return self::SUCCESS;
    }
}
