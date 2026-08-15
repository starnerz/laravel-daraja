<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Commands;

use Illuminate\Console\Command;
use Starnerz\LaravelDaraja\Daraja;
use Starnerz\LaravelDaraja\Exceptions\DarajaException;

class RegisterC2BUrls extends Command
{
    protected $signature = 'daraja:register-urls
                            {--confirmation= : Override the configured confirmation URL}
                            {--validation= : Override the configured validation URL}
                            {--short-code= : Override the configured short code}
                            {--response-type=Completed : Completed or Cancelled}';

    protected $description = 'Register your C2B confirmation and validation URLs with Safaricom';

    public function handle(Daraja $daraja): int
    {
        try {
            $result = $daraja->c2b()->registerUrls(
                confirmationUrl: $this->option('confirmation'),
                validationUrl: $this->option('validation'),
                responseType: (string) $this->option('response-type'),
                shortCode: $this->option('short-code'),
            );
        } catch (DarajaException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        if (! $result->accepted()) {
            $this->components->error("Safaricom rejected the registration: {$result->responseDescription}");

            return self::FAILURE;
        }

        $this->components->info('C2B URLs registered successfully.');
        $this->components->twoColumnDetail('Conversation ID', $result->originatorConversationId);

        return self::SUCCESS;
    }
}
