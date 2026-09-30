<?php

declare(strict_types=1);

namespace Siberfx\Turnstile;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Str;
use Siberfx\Turnstile\Enums\ErrorCode;
use Throwable;

class Turnstile
{
    /**
     * Cloudflare rejects tokens longer than this.
     */
    public const int MAX_TOKEN_LENGTH = 2048;

    public const string DEFAULT_VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public const string DEFAULT_SCRIPT_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js';

    public const string DEFAULT_FIELD = 'cf-turnstile-response';

    /**
     * Results of tokens already checked during this request. A token can
     * only be redeemed once, so the rule and the middleware share them.
     *
     * @var array<string, VerificationResult>
     */
    protected array $verified = [];

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected HttpFactory $http,
        protected array $config = [],
    ) {}

    public function enabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? true);
    }

    public function siteKey(): ?string
    {
        $key = $this->config['site_key'] ?? null;

        return is_string($key) && $key !== '' ? $key : null;
    }

    public function scriptUrl(): string
    {
        return (string) ($this->config['script_url'] ?? self::DEFAULT_SCRIPT_URL);
    }

    public function field(): string
    {
        return (string) ($this->config['field'] ?? self::DEFAULT_FIELD);
    }

    public function shouldSendRemoteIp(): bool
    {
        return (bool) ($this->config['send_remote_ip'] ?? true);
    }

    /**
     * @return list<string>
     */
    public function allowedHostnames(): array
    {
        $hostnames = $this->config['hostnames'] ?? [];

        if (is_string($hostnames)) {
            $hostnames = explode(',', $hostnames);
        }

        return array_values(array_filter(array_map(
            static fn (mixed $host): string => strtolower(trim((string) $host)),
            (array) $hostnames,
        )));
    }

    /**
     * Verify a widget token with Cloudflare.
     *
     * @param  string|null  $action  When given, the action reported by Cloudflare must match it.
     */
    public function verify(
        ?string $token,
        ?string $remoteIp = null,
        ?string $action = null,
        ?string $idempotencyKey = null,
    ): VerificationResult {
        if (! $this->enabled()) {
            return VerificationResult::passed();
        }

        if ($token === null || $token === '') {
            return VerificationResult::failed(ErrorCode::MissingInputResponse);
        }

        if (strlen($token) > self::MAX_TOKEN_LENGTH) {
            return VerificationResult::failed(ErrorCode::InvalidInputResponse);
        }

        $result = $this->verified[$token] ??= $this->siteverify($token, $remoteIp, $idempotencyKey);

        return $this->applyLocalChecks($result, $action);
    }

    /**
     * Forget results cached during this request.
     */
    public function flush(): void
    {
        $this->verified = [];
    }

    protected function siteverify(string $token, ?string $remoteIp, ?string $idempotencyKey): VerificationResult
    {
        $payload = array_filter([
            'secret' => (string) ($this->config['secret_key'] ?? ''),
            'response' => $token,
            'remoteip' => $this->shouldSendRemoteIp() ? $remoteIp : null,
            'idempotency_key' => $idempotencyKey ?? (string) Str::uuid(),
        ], static fn (?string $value): bool => $value !== null && $value !== '');

        try {
            $response = $this->http
                ->asForm()
                ->acceptJson()
                ->timeout((int) ($this->config['timeout'] ?? 5))
                ->retry(
                    times: max(0, (int) ($this->config['retries'] ?? 1)) + 1,
                    sleepMilliseconds: (int) ($this->config['retry_delay'] ?? 100),
                    when: static fn (Throwable $e): bool => $e instanceof ConnectionException,
                    throw: false,
                )
                ->post((string) ($this->config['verify_url'] ?? self::DEFAULT_VERIFY_URL), $payload);
        } catch (ConnectionException) {
            return VerificationResult::failed(ErrorCode::ConnectionFailed);
        }

        $json = $response->json();

        if (! is_array($json)) {
            return VerificationResult::failed(ErrorCode::InternalError);
        }

        return VerificationResult::fromResponse($json);
    }

    protected function applyLocalChecks(VerificationResult $result, ?string $action): VerificationResult
    {
        if ($result->fails()) {
            return $result;
        }

        $hostnames = $this->allowedHostnames();

        if ($hostnames !== [] && ! in_array(strtolower((string) $result->hostname), $hostnames, true)) {
            return $result->withError(ErrorCode::HostnameMismatch);
        }

        if ($action !== null && $action !== '' && $result->action !== $action) {
            return $result->withError(ErrorCode::ActionMismatch);
        }

        return $result;
    }
}
