<?php

declare(strict_types=1);

namespace Siberfx\Turnstile\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Siberfx\Turnstile\Turnstile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('turnstile') or ->middleware('turnstile:login').
 */
class VerifyTurnstile
{
    public function __construct(
        protected Turnstile $turnstile,
    ) {}

    public function handle(Request $request, Closure $next, ?string $action = null): Response
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $field = $this->turnstile->field();
        $token = $request->input($field);

        $result = $this->turnstile->verify(
            token: is_string($token) ? $token : null,
            remoteIp: $request->ip(),
            action: $action,
        );

        if ($result->fails()) {
            throw ValidationException::withMessages([
                $field => __('turnstile::validation.'.$result->messageKey()),
            ]);
        }

        return $next($request);
    }
}
