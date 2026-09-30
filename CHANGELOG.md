# Changelog

All notable changes to this package are documented here.

## [1.0.0] - 2026-09-30

### Added
- `<x-turnstile />` Blade component and `@turnstileScripts` directive
- `Siberfx\Turnstile\Rules\Turnstile` validation rule and `Rule::turnstile()` macro
- `turnstile` route middleware with optional expected action (`turnstile:login`)
- `VerificationResult` value object and `ErrorCode` enum
- Hostname allow-list, remote IP forwarding, connection retries, per-request token cache
- `Turnstile::fake()` testing helper with assertions
- `turnstile:publish` command and `turnstile` publish tag to publish config, views and translations at once
- Translations: English (en), Turkish (tr), Dutch (nl), German (de), Russian (ru)
- Support for PHP 8.4 / 8.5 and Laravel 12 / 13
