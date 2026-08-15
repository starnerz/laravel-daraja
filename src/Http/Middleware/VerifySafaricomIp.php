<?php

declare(strict_types=1);

namespace Starnerz\LaravelDaraja\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts callback endpoints to Safaricom's source addresses.
 *
 * The allow list is empty by default, which permits every request. Safaricom
 * publishes its ranges to partners rather than in the public documentation, so
 * no addresses are bundled here — populate
 * laravel-daraja.security.allowed_ips with the list they give you, in plain or
 * CIDR form, and this middleware starts enforcing.
 *
 * Note that a callback carries no signature or shared secret, so an address
 * check is the only verification available. Treat callback contents as
 * unverified input regardless and confirm value with Transaction Status before
 * releasing anything.
 */
class VerifySafaricomIp
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var list<string> $allowed */
        $allowed = config('laravel-daraja.security.allowed_ips', []);

        if ($allowed === []) {
            return $next($request);
        }

        if (! IpUtils::checkIp((string) $request->ip(), $allowed)) {
            abort(403, 'Callback rejected: source address is not a permitted Safaricom address.');
        }

        return $next($request);
    }
}
