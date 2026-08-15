<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Support;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Router;
use Starnerz\LaravelDaraja\Exceptions\ConfigurationException;

/**
 * Resolves a configured callback value into an absolute URL.
 *
 * Configuration files cannot call route(), so callback settings may hold either
 * a route name or a full URL. This resolves whichever was given.
 */
final class CallbackUrl
{
    public function __construct(
        private readonly Router $router,
        private readonly UrlGenerator $url,
    ) {}

    public function resolve(?string $value, ?string $configKey = null): string
    {
        if (blank($value)) {
            throw ConfigurationException::missing($configKey ?? 'urls');
        }

        if ($this->router->has($value)) {
            return $this->url->route($value);
        }

        return $value;
    }

    /**
     * Resolve a value that may legitimately be absent.
     */
    public function resolveOptional(?string $value): ?string
    {
        return blank($value) ? null : $this->resolve($value);
    }
}
