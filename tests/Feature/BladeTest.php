<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renders the widget with configured defaults', function () {
    $html = Blade::render('<x-turnstile />');

    expect($html)
        ->toContain('<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>')
        ->toContain('class="cf-turnstile"')
        ->toContain('data-sitekey="1x00000000000000000000AA"')
        ->toContain('data-theme="auto"')
        ->toContain('data-size="normal"');
});

it('accepts per-instance options and extra attributes', function () {
    $html = Blade::render('<x-turnstile theme="dark" action="login" callback="onPass" :script="false" id="captcha" class="mt-4" />');

    expect($html)
        ->not->toContain('<script')
        ->toContain('data-theme="dark"')
        ->toContain('data-action="login"')
        ->toContain('data-callback="onPass"')
        ->toContain('id="captcha"')
        ->toContain('class="cf-turnstile mt-4"');
});

it('includes the script only once per page', function () {
    $html = Blade::render('<x-turnstile /><x-turnstile />');

    expect(substr_count($html, '<script'))->toBe(1)
        ->and(substr_count($html, 'cf-turnstile'))->toBe(2);
});

it('sets a custom response field name when configured', function () {
    config(['turnstile.field' => 'captcha']);

    expect(Blade::render('<x-turnstile />'))->toContain('data-response-field-name="captcha"');
});

it('provides a scripts directive', function () {
    expect(Blade::render('@turnstileScripts'))
        ->toBe('<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>');
});
