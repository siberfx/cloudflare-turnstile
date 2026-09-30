<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Siberfx\Turnstile\Enums\ErrorCode;
use Siberfx\Turnstile\Facades\Turnstile;
use Siberfx\Turnstile\Rules\Turnstile as TurnstileRule;

it('passes with a valid token', function () {
    Turnstile::fake();

    $validator = Validator::make(['cf-turnstile-response' => 'ok'], [
        'cf-turnstile-response' => [new TurnstileRule],
    ]);

    expect($validator->passes())->toBeTrue();
    Turnstile::assertVerified('ok');
});

it('fails when the token is missing', function () {
    successfulSiteverify();

    $validator = Validator::make([], ['cf-turnstile-response' => [new TurnstileRule]]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('cf-turnstile-response'))
        ->toBe('Please complete the CAPTCHA challenge.');
});

it('shows an expiry message for reused tokens', function () {
    Turnstile::fake()->fail(ErrorCode::TimeoutOrDuplicate);

    $validator = Validator::make(['t' => 'x'], ['t' => [new TurnstileRule]]);

    expect($validator->errors()->first('t'))->toContain('expired');
});

it('forwards the expected action via the Rule macro', function () {
    $fake = Turnstile::fake();

    Validator::make(['t' => 'x'], ['t' => [Rule::turnstile('login')]])->passes();

    expect($fake->recorded()[0]['action'])->toBe('login');
});

it('is translatable', function () {
    app()->setLocale('tr');
    Turnstile::fake(passes: false);

    $validator = Validator::make(['t' => 'x'], ['t' => [TurnstileRule::action('login')]]);

    expect($validator->errors()->first('t'))->toBe('CAPTCHA doğrulaması başarısız oldu. Lütfen tekrar deneyin.');
});
