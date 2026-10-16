<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Exceptions;

use RuntimeException;

/**
 * Base class for every exception thrown by this package.
 */
class DarajaException extends RuntimeException
{
    public const DOCS = 'https://laraveldaraja.com';

    /**
     * The documentation page that explains this failure.
     */
    public function docsUrl(): string
    {
        return self::DOCS.'/reference/exceptions/';
    }

    /**
     * Added to the log entry when Laravel reports the exception, so the
     * page that explains it is one click from the log.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return ['docs' => $this->docsUrl()];
    }
}
