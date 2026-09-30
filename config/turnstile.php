<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | When disabled, every verification succeeds without contacting
    | Cloudflare. Handy for local development and CI pipelines.
    |
    */

    'enabled' => env('TURNSTILE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Keys
    |--------------------------------------------------------------------------
    |
    | Obtain these from the Cloudflare dashboard (Turnstile > Add widget).
    |
    */

    'site_key' => env('TURNSTILE_SITE_KEY'),

    'secret_key' => env('TURNSTILE_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Endpoints
    |--------------------------------------------------------------------------
    */

    'verify_url' => env('TURNSTILE_VERIFY_URL', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),

    'script_url' => env('TURNSTILE_SCRIPT_URL', 'https://challenges.cloudflare.com/turnstile/v0/api.js'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client
    |--------------------------------------------------------------------------
    |
    | Timeout is in seconds. Retries only happen on connection failures;
    | a token is single-use, so a received answer is never re-requested.
    |
    */

    'timeout' => (int) env('TURNSTILE_TIMEOUT', 5),

    'retries' => (int) env('TURNSTILE_RETRIES', 1),

    'retry_delay' => (int) env('TURNSTILE_RETRY_DELAY', 100),

    /*
    |--------------------------------------------------------------------------
    | Request Field
    |--------------------------------------------------------------------------
    |
    | The form field the widget writes its token into.
    |
    */

    'field' => 'cf-turnstile-response',

    /*
    |--------------------------------------------------------------------------
    | Remote IP
    |--------------------------------------------------------------------------
    |
    | Forward the visitor's IP to Cloudflare for an additional check.
    |
    */

    'send_remote_ip' => env('TURNSTILE_SEND_REMOTE_IP', true),

    /*
    |--------------------------------------------------------------------------
    | Allowed Hostnames
    |--------------------------------------------------------------------------
    |
    | If not empty, verification fails when the hostname reported by
    | Cloudflare is not in this list. Accepts an array or a
    | comma separated string.
    |
    */

    'hostnames' => env('TURNSTILE_HOSTNAMES', []),

    /*
    |--------------------------------------------------------------------------
    | Widget Defaults
    |--------------------------------------------------------------------------
    |
    | Default data-* options used by the <x-turnstile /> component.
    | Each can be overridden per component instance.
    |
    */

    'widget' => [
        'theme' => env('TURNSTILE_THEME', 'auto'),       // auto | light | dark
        'size' => env('TURNSTILE_SIZE', 'normal'),       // normal | flexible | compact
        'appearance' => 'always',                        // always | execute | interaction-only
        'language' => 'auto',
    ],

];
