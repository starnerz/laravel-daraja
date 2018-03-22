<?php

namespace Starnerz\LaravelDaraja\Facades;

use Illuminate\Support\Facades\Facade;

class MpesaApi extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'mpesa-api';
    }
}
