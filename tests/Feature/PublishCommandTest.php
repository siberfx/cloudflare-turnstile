<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->targets = [
        config_path('turnstile.php'),
        resource_path('views/vendor/turnstile/components/turnstile.blade.php'),
        lang_path('vendor/turnstile/en/validation.php'),
        lang_path('vendor/turnstile/de/validation.php'),
    ];

    $cleanup = function () {
        File::delete(config_path('turnstile.php'));
        File::deleteDirectory(resource_path('views/vendor/turnstile'));
        File::deleteDirectory(lang_path('vendor/turnstile'));
    };

    $cleanup();
    $this->beforeApplicationDestroyed($cleanup);
});

it('publishes config, views and translations in one go', function () {
    $this->artisan('turnstile:publish')->assertSuccessful();

    foreach ($this->targets as $path) {
        expect($path)->toBeFile();
    }
});

it('keeps existing files unless forced', function () {
    File::put(config_path('turnstile.php'), '<?php return [];');

    $this->artisan('turnstile:publish')->assertSuccessful();
    expect(File::get(config_path('turnstile.php')))->toBe('<?php return [];');

    $this->artisan('turnstile:publish', ['--force' => true])->assertSuccessful();
    expect(File::get(config_path('turnstile.php')))->toContain('TURNSTILE_SITE_KEY');
});
