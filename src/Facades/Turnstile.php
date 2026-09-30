<?php

declare(strict_types=1);

namespace Siberfx\Turnstile\Facades;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Facade;
use Siberfx\Turnstile\Testing\TurnstileFake;
use Siberfx\Turnstile\Turnstile as TurnstileClient;

/**
 * @method static \Siberfx\Turnstile\VerificationResult verify(?string $token, ?string $remoteIp = null, ?string $action = null, ?string $idempotencyKey = null)
 * @method static bool enabled()
 * @method static string|null siteKey()
 * @method static string scriptUrl()
 * @method static string field()
 * @method static list<string> allowedHostnames()
 * @method static void flush()
 * @method static void assertVerified(?string $token = null)
 * @method static void assertNotVerified()
 * @method static void assertVerifiedTimes(int $times)
 *
 * @see TurnstileClient
 * @see TurnstileFake
 */
class Turnstile extends Facade
{
    /**
     * Replace the bound instance with a fake that never calls Cloudflare.
     */
    public static function fake(bool $passes = true): TurnstileFake
    {
        $fake = new TurnstileFake(
            app(HttpFactory::class),
            (array) config('turnstile', []),
            $passes,
        );

        static::swap($fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return TurnstileClient::class;
    }
}
