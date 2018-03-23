<?php

namespace Starnerz\LaravelDaraja\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Starnerz\LaravelDaraja\Facades\MpesaApi;

class RegisterC2BUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daraja:register-urls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Registers C2B URLs to the Safaricom C2B API';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $confirmation = config('mpesaapi.c2b_url.confirmation');
        $validation = config('mpesaapi.c2b_url.validation');

        MpesaAPI::c2b()->registerUrls($confirmation, $validation);
        $this->info('URLs registered successfully');
    }

}
