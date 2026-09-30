<?php

declare(strict_types=1);

$locales = array_map(basename(...), glob(__DIR__.'/../../lang/*', GLOB_ONLYDIR));

it('ships every message key for each locale', function (string $locale) {
    $base = require __DIR__.'/../../lang/en/validation.php';
    $messages = require __DIR__."/../../lang/{$locale}/validation.php";

    expect(array_keys($messages))->toEqualCanonicalizing(array_keys($base))
        ->and(array_filter($messages, fn ($message) => ! is_string($message) || $message === ''))->toBeEmpty();
})->with($locales);

it('resolves translated messages', function (string $locale, string $expected) {
    app()->setLocale($locale);

    expect(__('turnstile::validation.missing'))->toBe($expected);
})->with([
    ['en', 'Please complete the CAPTCHA challenge.'],
    ['tr', 'Lütfen CAPTCHA doğrulamasını tamamlayın.'],
    ['nl', 'Voltooi de CAPTCHA-controle.'],
    ['de', 'Bitte schließen Sie die CAPTCHA-Überprüfung ab.'],
    ['ru', 'Пожалуйста, пройдите проверку CAPTCHA.'],
]);
