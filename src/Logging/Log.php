<?php

namespace Starnerz\LaravelDaraja\Logging;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Log
{
    /**
     * All the available debug levels.
     *
     * @var array
     */
    protected static $levels = [
        'DEBUG' => Logger::DEBUG,
        'INFO' => Logger::INFO,
        'NOTICE' => Logger::NOTICE,
        'WARNING' => Logger::WARNING,
        'ERROR' => Logger::ERROR,
        'CRITICAL' => Logger::CRITICAL,
        'ALERT' => Logger::ALERT,
        'EMERGENCY' => Logger::EMERGENCY,
    ];

    /**
     * Set up the logging requirements for the Guzzle package.
     *
     * @param $options
     * @return int
     */
    public static function enable($options)
    {
        $level = self::getLogLevel();

        $handler = new Logger(
            'LaravelDaraja',
            [
                new StreamHandler(storage_path('logs/laravel-daraja.log'), $level),
            ]

        );

        $stack = HandlerStack::create();
        $stack->push(
            Middleware::log(
                $handler,
                new MessageFormatter('{method} {uri} HTTP/{version} {req_body} RESPONSE: {code} - {res_body}')
            )
        );

        $options['handler'] = $stack;

        return $options;
    }

    /**
     * Determine the log level specified in the configurations.
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected static function getLogLevel()
    {
        $level = strtoupper(config('laravel-daraja.logs.level'));

        if (array_key_exists($level, self::$levels)) {
            return self::$levels[$level];
        }

        throw new \Exception('Debug level not recognized');
    }
}
