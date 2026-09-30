<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Siberfx\Turnstile\Enums\ErrorCode;
use Siberfx\Turnstile\Facades\Turnstile;

it('verifies a valid token', function () {
    successfulSiteverify();

    $result = Turnstile::verify('token', '203.0.113.7');

    expect($result->passes())->toBeTrue();

    Http::assertSent(fn (Request $request) => $request->isForm()
        && $request['secret'] === '1x0000000000000000000000000000000AA'
        && $request['response'] === 'token'
        && $request['remoteip'] === '203.0.113.7'
        && is_string($request['idempotency_key']));
});

it('omits the remote ip when disabled', function () {
    config(['turnstile.send_remote_ip' => false]);
    successfulSiteverify();

    Turnstile::verify('token', '203.0.113.7');

    Http::assertSent(fn (Request $request) => ! isset($request->data()['remoteip']));
});

it('returns Cloudflare error codes', function () {
    fakeSiteverify(['success' => false, 'error-codes' => ['invalid-input-response']]);

    $result = Turnstile::verify('bad');

    expect($result->fails())->toBeTrue()
        ->and($result->hasError(ErrorCode::InvalidInputResponse))->toBeTrue();
});

it('fails locally without a request for a missing or oversized token', function () {
    Http::preventStrayRequests();
    Http::fake();

    expect(Turnstile::verify(null)->hasError(ErrorCode::MissingInputResponse))->toBeTrue()
        ->and(Turnstile::verify('')->hasError(ErrorCode::MissingInputResponse))->toBeTrue()
        ->and(Turnstile::verify(str_repeat('a', 2049))->hasError(ErrorCode::InvalidInputResponse))->toBeTrue();

    Http::assertNothingSent();
});

it('passes everything when disabled', function () {
    config(['turnstile.enabled' => false]);
    Http::preventStrayRequests();
    Http::fake();

    expect(Turnstile::verify(null)->passes())->toBeTrue();

    Http::assertNothingSent();
});

it('enforces allowed hostnames', function () {
    config(['turnstile.hostnames' => 'example.com, www.example.com']);
    successfulSiteverify(['hostname' => 'evil.test']);

    expect(Turnstile::verify('token')->hasError(ErrorCode::HostnameMismatch))->toBeTrue();
});

it('accepts a matching hostname case-insensitively', function () {
    config(['turnstile.hostnames' => ['Example.com']]);
    successfulSiteverify(['hostname' => 'example.COM']);

    expect(Turnstile::verify('token')->passes())->toBeTrue();
});

it('enforces the expected action', function () {
    successfulSiteverify(['action' => 'signup']);

    expect(Turnstile::verify('token', action: 'login')->hasError(ErrorCode::ActionMismatch))->toBeTrue()
        ->and(Turnstile::verify('token', action: 'signup')->passes())->toBeTrue();
});

it('redeems a token only once per request', function () {
    successfulSiteverify();

    Turnstile::verify('token');
    Turnstile::verify('token');

    Http::assertSentCount(1);
});

it('reports connection failures', function () {
    Http::fake(fn () => throw new ConnectionException('down'));

    $result = Turnstile::verify('token');

    expect($result->hasError(ErrorCode::ConnectionFailed))->toBeTrue()
        ->and($result->messageKey())->toBe('unavailable');
});

it('reports a non-json response as an internal error', function () {
    Http::fake([Siberfx\Turnstile\Turnstile::DEFAULT_VERIFY_URL => Http::response('<html>', 502)]);

    expect(Turnstile::verify('token')->hasError(ErrorCode::InternalError))->toBeTrue();
});
