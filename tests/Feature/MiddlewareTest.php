<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Siberfx\Turnstile\Facades\Turnstile;

beforeEach(function () {
    Route::middleware('turnstile')->match(['get', 'post'], '/contact', fn () => 'sent');
    Route::middleware('turnstile:login')->post('/login', fn () => 'in');
});

it('skips safe methods', function () {
    Turnstile::fake(passes: false);

    $this->get('/contact')->assertOk();
    Turnstile::assertNotVerified();
});

it('lets a verified request through', function () {
    Turnstile::fake();

    $this->post('/contact', ['cf-turnstile-response' => 'ok'])->assertOk()->assertSee('sent');
    Turnstile::assertVerified('ok');
});

it('rejects an unverified request with a validation error', function () {
    Turnstile::fake(passes: false);

    $this->postJson('/contact', ['cf-turnstile-response' => 'bad'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('cf-turnstile-response');
});

it('passes the action parameter', function () {
    $fake = Turnstile::fake();

    $this->post('/login', ['cf-turnstile-response' => 'ok'])->assertOk();

    expect($fake->recorded()[0]['action'])->toBe('login');
});
