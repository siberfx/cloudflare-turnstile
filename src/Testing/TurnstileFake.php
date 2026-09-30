<?php

declare(strict_types=1);

namespace Siberfx\Turnstile\Testing;

use Closure;
use Illuminate\Http\Client\Factory as HttpFactory;
use PHPUnit\Framework\Assert as PHPUnit;
use Siberfx\Turnstile\Enums\ErrorCode;
use Siberfx\Turnstile\Turnstile;
use Siberfx\Turnstile\VerificationResult;

class TurnstileFake extends Turnstile
{
    /**
     * @var list<array{token: string|null, remote_ip: string|null, action: string|null}>
     */
    protected array $recorded = [];

    /**
     * @var VerificationResult|Closure(string|null, string|null, string|null): VerificationResult
     */
    protected VerificationResult|Closure $response;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(HttpFactory $http, array $config = [], bool $passes = true)
    {
        parent::__construct($http, $config);

        $passes ? $this->pass() : $this->fail();
    }

    public function pass(): static
    {
        $this->response = VerificationResult::passed();

        return $this;
    }

    public function fail(ErrorCode|string $code = ErrorCode::InvalidInputResponse): static
    {
        $this->response = VerificationResult::failed($code);

        return $this;
    }

    /**
     * @param  VerificationResult|Closure(string|null, string|null, string|null): VerificationResult  $response
     */
    public function respondWith(VerificationResult|Closure $response): static
    {
        $this->response = $response;

        return $this;
    }

    public function verify(
        ?string $token,
        ?string $remoteIp = null,
        ?string $action = null,
        ?string $idempotencyKey = null,
    ): VerificationResult {
        $this->recorded[] = ['token' => $token, 'remote_ip' => $remoteIp, 'action' => $action];

        return $this->response instanceof Closure
            ? ($this->response)($token, $remoteIp, $action)
            : $this->response;
    }

    public function assertVerified(?string $token = null): void
    {
        $matches = $token === null
            ? $this->recorded
            : array_filter($this->recorded, static fn (array $call): bool => $call['token'] === $token);

        PHPUnit::assertNotEmpty(
            $matches,
            $token === null ? 'No Turnstile verification was attempted.' : "Turnstile token [{$token}] was not verified.",
        );
    }

    public function assertNotVerified(): void
    {
        PHPUnit::assertEmpty($this->recorded, 'An unexpected Turnstile verification was attempted.');
    }

    public function assertVerifiedTimes(int $times): void
    {
        PHPUnit::assertCount($times, $this->recorded, "Expected {$times} Turnstile verification(s), got ".count($this->recorded).'.');
    }

    /**
     * @return list<array{token: string|null, remote_ip: string|null, action: string|null}>
     */
    public function recorded(): array
    {
        return $this->recorded;
    }
}
