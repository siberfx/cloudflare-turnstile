<?php

declare(strict_types=1);

namespace Siberfx\Turnstile\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;
use Siberfx\Turnstile\Turnstile as TurnstileClient;

class Turnstile implements ValidationRule
{
    /**
     * Run even when the field is absent, so a missing token fails.
     */
    public bool $implicit = true;

    final public function __construct(
        protected ?string $action = null,
    ) {}

    public static function action(string $action): static
    {
        return new static($action);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $result = app(TurnstileClient::class)->verify(
            token: is_string($value) ? $value : null,
            remoteIp: app(Request::class)->ip(),
            action: $this->action,
        );

        if ($result->fails()) {
            $fail('turnstile::validation.'.$result->messageKey())->translate();
        }
    }
}
