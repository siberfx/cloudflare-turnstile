<?php

declare(strict_types=1);

namespace Siberfx\Turnstile;

use DateTimeImmutable;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Siberfx\Turnstile\Enums\ErrorCode;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class VerificationResult implements Arrayable
{
    /**
     * @param  list<string>  $errorCodes
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public bool $success,
        public array $errorCodes = [],
        public ?string $hostname = null,
        public ?string $action = null,
        public ?string $cdata = null,
        public ?DateTimeImmutable $challengedAt = null,
        public ?string $ephemeralId = null,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromResponse(array $payload): self
    {
        $metadata = is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [];

        return new self(
            success: ($payload['success'] ?? false) === true,
            errorCodes: array_values(array_map(strval(...), (array) ($payload['error-codes'] ?? []))),
            hostname: self::stringOrNull($payload['hostname'] ?? null),
            action: self::stringOrNull($payload['action'] ?? null),
            cdata: self::stringOrNull($payload['cdata'] ?? null),
            challengedAt: self::dateOrNull($payload['challenge_ts'] ?? null),
            ephemeralId: self::stringOrNull($metadata['ephemeral_id'] ?? null),
            raw: $payload,
        );
    }

    public static function passed(): self
    {
        return new self(success: true);
    }

    public static function failed(ErrorCode|string ...$codes): self
    {
        return new self(
            success: false,
            errorCodes: array_values(array_map(
                static fn (ErrorCode|string $code): string => $code instanceof ErrorCode ? $code->value : $code,
                $codes,
            )),
        );
    }

    public function passes(): bool
    {
        return $this->success;
    }

    public function fails(): bool
    {
        return ! $this->success;
    }

    /**
     * Known error codes as enums; unknown codes are skipped.
     *
     * @return list<ErrorCode>
     */
    public function errors(): array
    {
        return array_values(array_filter(array_map(ErrorCode::tryFrom(...), $this->errorCodes)));
    }

    public function hasError(ErrorCode|string $code): bool
    {
        return in_array($code instanceof ErrorCode ? $code->value : $code, $this->errorCodes, true);
    }

    /**
     * Return a copy that is marked as failed with an additional error.
     */
    public function withError(ErrorCode $code): self
    {
        return new self(
            success: false,
            errorCodes: [...$this->errorCodes, $code->value],
            hostname: $this->hostname,
            action: $this->action,
            cdata: $this->cdata,
            challengedAt: $this->challengedAt,
            ephemeralId: $this->ephemeralId,
            raw: $this->raw,
        );
    }

    /**
     * Translation key (under "turnstile::validation") describing the failure.
     */
    public function messageKey(): string
    {
        return ($this->errors()[0] ?? null)?->messageKey() ?? 'failed';
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'error_codes' => $this->errorCodes,
            'hostname' => $this->hostname,
            'action' => $this->action,
            'cdata' => $this->cdata,
            'challenged_at' => $this->challengedAt?->format(DATE_ATOM),
            'ephemeral_id' => $this->ephemeralId,
        ];
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function dateOrNull(mixed $value): ?DateTimeImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }
    }
}
