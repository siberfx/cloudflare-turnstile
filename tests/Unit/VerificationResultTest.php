<?php

declare(strict_types=1);

use Siberfx\Turnstile\Enums\ErrorCode;
use Siberfx\Turnstile\VerificationResult;

it('hydrates from a siteverify payload', function () {
    $result = VerificationResult::fromResponse([
        'success' => true,
        'challenge_ts' => '2026-09-30T12:00:00.000Z',
        'hostname' => 'example.com',
        'error-codes' => [],
        'action' => 'login',
        'cdata' => 'abc',
        'metadata' => ['ephemeral_id' => 'x:1'],
    ]);

    expect($result->passes())->toBeTrue()
        ->and($result->hostname)->toBe('example.com')
        ->and($result->action)->toBe('login')
        ->and($result->cdata)->toBe('abc')
        ->and($result->ephemeralId)->toBe('x:1')
        ->and($result->challengedAt?->format('Y-m-d'))->toBe('2026-09-30');
});

it('treats anything but a strict true as failure', function () {
    expect(VerificationResult::fromResponse(['success' => 'true'])->fails())->toBeTrue()
        ->and(VerificationResult::fromResponse([])->fails())->toBeTrue();
});

it('maps known error codes and ignores unknown ones', function () {
    $result = VerificationResult::failed('timeout-or-duplicate', 'something-new');

    expect($result->errors())->toBe([ErrorCode::TimeoutOrDuplicate])
        ->and($result->hasError('something-new'))->toBeTrue()
        ->and($result->messageKey())->toBe('expired');
});

it('marks a copy as failed when adding an error', function () {
    $passed = VerificationResult::fromResponse(['success' => true, 'hostname' => 'a.test']);
    $failed = $passed->withError(ErrorCode::HostnameMismatch);

    expect($passed->passes())->toBeTrue()
        ->and($failed->fails())->toBeTrue()
        ->and($failed->hostname)->toBe('a.test')
        ->and($failed->errorCodes)->toBe(['hostname-mismatch']);
});

it('tolerates a malformed timestamp', function () {
    expect(VerificationResult::fromResponse(['success' => true, 'challenge_ts' => 'nope'])->challengedAt)->toBeNull();
});
