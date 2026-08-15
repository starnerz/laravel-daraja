<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Commands;

use Illuminate\Console\Command;
use Starnerz\LaravelDaraja\Daraja;
use Starnerz\LaravelDaraja\Exceptions\DarajaException;
use Starnerz\LaravelDaraja\Support\SecurityCredential;

class ShowSecurityCredential extends Command
{
    protected $signature = 'daraja:credential {password? : The initiator password to encrypt}';

    protected $description = 'Encrypt an initiator password into a Daraja SecurityCredential';

    public function handle(Daraja $daraja, SecurityCredential $credential): int
    {
        $password = $this->argument('password');

        try {
            $encrypted = $daraja->securityCredential($password);
        } catch (DarajaException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Mode', (string) config('laravel-daraja.mode'));
        $this->components->twoColumnDetail('Certificate', $credential->certificatePath());
        $this->newLine();
        $this->line($encrypted);

        return self::SUCCESS;
    }
}
