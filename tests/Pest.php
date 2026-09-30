<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Siberfx\Turnstile\Tests\TestCase;
use Siberfx\Turnstile\Turnstile;

pest()->extend(TestCase::class)->in('Feature', 'Unit');

/**
 * Fake Cloudflare's siteverify endpoint with the given JSON body.
 *
 * @param  array<string, mixed>  $body
 */
function fakeSiteverify(array $body): void
{
    Http::preventStrayRequests();
    Http::fake([Turnstile::DEFAULT_VERIFY_URL => Http::response($body)]);
}

function successfulSiteverify(array $overrides = []): void
{
    fakeSiteverify([
        'success' => true,
        'challenge_ts' => '2026-09-30T12:00:00.000Z',
        'hostname' => 'example.com',
        'error-codes' => [],
        'action' => 'login',
        'cdata' => 'session-42',
        'metadata' => ['ephemeral_id' => 'x:abc123'],
        ...$overrides,
    ]);
}
