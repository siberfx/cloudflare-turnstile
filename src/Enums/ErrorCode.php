<?php

declare(strict_types=1);

namespace Siberfx\Turnstile\Enums;

enum ErrorCode: string
{
    // Returned by Cloudflare's siteverify endpoint.
    case MissingInputSecret = 'missing-input-secret';
    case InvalidInputSecret = 'invalid-input-secret';
    case MissingInputResponse = 'missing-input-response';
    case InvalidInputResponse = 'invalid-input-response';
    case InvalidWidgetId = 'invalid-widget-id';
    case InvalidParsedSecret = 'invalid-parsed-secret';
    case BadRequest = 'bad-request';
    case TimeoutOrDuplicate = 'timeout-or-duplicate';
    case InternalError = 'internal-error';

    // Raised locally by this package.
    case ConnectionFailed = 'connection-failed';
    case HostnameMismatch = 'hostname-mismatch';
    case ActionMismatch = 'action-mismatch';

    /**
     * Translation key (under "turnstile::validation") shown to the end user.
     */
    public function messageKey(): string
    {
        return match ($this) {
            self::MissingInputResponse => 'missing',
            self::TimeoutOrDuplicate => 'expired',
            self::ConnectionFailed, self::InternalError => 'unavailable',
            default => 'failed',
        };
    }

    /**
     * Whether the failure is caused by server misconfiguration rather than the visitor.
     */
    public function isConfigurationError(): bool
    {
        return in_array($this, [
            self::MissingInputSecret,
            self::InvalidInputSecret,
            self::InvalidParsedSecret,
            self::InvalidWidgetId,
        ], true);
    }
}
