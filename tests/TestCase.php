<?php

declare(strict_types=1);

namespace Siberfx\Turnstile\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Siberfx\Turnstile\TurnstileServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [TurnstileServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('turnstile.site_key', '1x00000000000000000000AA');
        $app['config']->set('turnstile.secret_key', '1x0000000000000000000000000000000AA');
        $app['config']->set('turnstile.retries', 0);
    }
}
