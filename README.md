# Cloudflare Turnstile for Laravel

Protect your Laravel forms with [Cloudflare Turnstile](https://developers.cloudflare.com/turnstile/), the privacy-first CAPTCHA alternative.

- `<x-turnstile />` Blade component. It loads the script once per page.
- Validation rule (`new Turnstile`, `Rule::turnstile()`), with expected-action support
- `turnstile` route middleware
- Optional hostname allow-list and remote IP forwarding
- Rich `VerificationResult` object (hostname, action, cdata, timestamp, ephemeral ID)
- Octane-safe. Tokens are redeemed once per request, even if the rule and middleware both run
- `Turnstile::fake()` for your own tests
- Translations: English, Turkish, Dutch, German and Russian

## Requirements

| Package | Version        |
| ------- | -------------- |
| PHP     | 8.4, 8.5       |
| Laravel | 12.x, 13.x     |

## Installation

```bash
composer require siberfx/cloudflare-turnstile
```

Add your keys (Cloudflare dashboard → Turnstile → Add widget) to `.env`:

```dotenv
TURNSTILE_SITE_KEY=0x4AAAAAAA...
TURNSTILE_SECRET_KEY=0x4AAAAAAA...
```

Optionally publish everything (config, views and translations) with a single command:

```bash
php artisan turnstile:publish          # add --force to overwrite existing files
# equivalent to: php artisan vendor:publish --tag=turnstile
```

Or publish only what you need:

```bash
php artisan vendor:publish --tag=turnstile-config
php artisan vendor:publish --tag=turnstile-views
php artisan vendor:publish --tag=turnstile-lang
```

## Rendering the widget

Place the component inside your form:

```blade
<form method="POST" action="/contact">
    @csrf
    <!-- ... -->
    <x-turnstile />

    @error('cf-turnstile-response')
        <p class="text-red-600">{{ $message }}</p>
    @enderror

    <button type="submit">Send</button>
</form>
```

The Cloudflare script is included automatically, only once per page. To load it yourself (e.g. in your `<head>`), pass `:script="false"` and use the directive:

```blade
@turnstileScripts
```

### Options

Every option maps to a `data-*` attribute. Defaults come from `config('turnstile.widget')`:

```blade
<x-turnstile
    theme="dark"               {{-- auto | light | dark --}}
    size="flexible"            {{-- normal | flexible | compact --}}
    appearance="interaction-only"
    language="tr"
    action="login"
    cdata="user-42"
    callback="onTurnstileSuccess"
    error-callback="onTurnstileError"
    expired-callback="onTurnstileExpired"
    id="captcha"
    class="mt-4"
/>
```

## Validating

### Validation rule

```php
use Siberfx\Turnstile\Rules\Turnstile;

$request->validate([
    'email' => ['required', 'email'],
    'cf-turnstile-response' => [new Turnstile],
]);
```

The rule is *implicit*, so a missing token also fails. To check that the token came from a widget with a specific `action`, use:

```php
use Illuminate\Validation\Rule;

'cf-turnstile-response' => [Turnstile::action('login')],
// or
'cf-turnstile-response' => [Rule::turnstile('login')],
```

### Middleware

```php
Route::post('/contact', ContactController::class)->middleware('turnstile');
Route::post('/login', LoginController::class)->middleware('turnstile:login');
```

Safe methods (`GET`, `HEAD`, `OPTIONS`) are skipped. When verification fails, the middleware throws a `ValidationException` on the `cf-turnstile-response` field. You get the usual redirect with errors, or a `422` JSON response.

### Manual verification

```php
use Siberfx\Turnstile\Facades\Turnstile;

$result = Turnstile::verify(
    token: $request->input('cf-turnstile-response'),
    remoteIp: $request->ip(),
    action: 'checkout',          // optional
);

if ($result->fails()) {
    logger()->warning('Turnstile failed', $result->toArray());
}

$result->hostname;      // "example.com"
$result->action;        // "checkout"
$result->cdata;         // custom data from the widget
$result->challengedAt;  // DateTimeImmutable
$result->ephemeralId;   // Enterprise only
$result->errors();      // list<ErrorCode>
```

## Error handling

Error codes are exposed as the `Siberfx\Turnstile\Enums\ErrorCode` enum. Next to Cloudflare's own codes, the package adds:

| Code                | Meaning                                                   |
| ------------------- | --------------------------------------------------------- |
| `connection-failed` | Cloudflare could not be reached (after the retries you configured) |
| `hostname-mismatch` | Hostname isn't in `turnstile.hostnames`                   |
| `action-mismatch`   | Widget action differs from the expected action            |

Visitors see one of four friendly messages: `failed`, `missing`, `expired`, `unavailable`. Publish the translations to change them.

`ErrorCode::isConfigurationError()` helps you tell a wrong secret key apart from a bad visitor.

## Configuration

| Key              | Env                        | Default                  |
| ---------------- | -------------------------- | ------------------------ |
| `enabled`        | `TURNSTILE_ENABLED`        | `true`                   |
| `site_key`       | `TURNSTILE_SITE_KEY`       | `null`                   |
| `secret_key`     | `TURNSTILE_SECRET_KEY`     | `null`                   |
| `timeout`        | `TURNSTILE_TIMEOUT`        | `5` seconds              |
| `retries`        | `TURNSTILE_RETRIES`        | `1` (connection errors only) |
| `send_remote_ip` | `TURNSTILE_SEND_REMOTE_IP` | `true`                   |
| `hostnames`      | `TURNSTILE_HOSTNAMES`      | `[]` (comma separated in env) |
| `field`          |                            | `cf-turnstile-response`  |
| `widget.*`       | `TURNSTILE_THEME`, `TURNSTILE_SIZE` | `auto`, `normal` |

When `enabled` is `false`, every verification passes without a network call. This is useful in local development.

### Cloudflare test keys

| Site key                   | Behaviour       |
| -------------------------- | --------------- |
| `1x00000000000000000000AA` | Always passes   |
| `2x00000000000000000000AB` | Always blocks   |
| `3x00000000000000000000FF` | Forces a challenge |

| Secret key                            | Behaviour                  |
| ------------------------------------- | -------------------------- |
| `1x0000000000000000000000000000000AA` | Always passes              |
| `2x0000000000000000000000000000000AA` | Always fails               |
| `3x0000000000000000000000000000000AA` | Yields "token already spent" |

## Testing your application

```php
use Siberfx\Turnstile\Enums\ErrorCode;
use Siberfx\Turnstile\Facades\Turnstile;

it('sends the contact form', function () {
    Turnstile::fake();

    $this->post('/contact', ['cf-turnstile-response' => 'any'])->assertRedirect();

    Turnstile::assertVerified('any');
});

it('rejects bots', function () {
    Turnstile::fake(passes: false);
    // or: Turnstile::fake()->fail(ErrorCode::TimeoutOrDuplicate);

    $this->post('/contact')->assertSessionHasErrors('cf-turnstile-response');
});
```

Other fake helpers: `respondWith(VerificationResult|Closure)`, `assertNotVerified()`, `assertVerifiedTimes(int)`, `recorded()`.

## Development

```bash
composer test      # Pest
composer analyse   # PHPStan / Larastan (level 8)
composer format    # Pint
```

## License

MIT. See [LICENSE](LICENSE).
