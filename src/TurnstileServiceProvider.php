<?php

declare(strict_types=1);

namespace Siberfx\Turnstile;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rule;
use Siberfx\Turnstile\Console\PublishCommand;
use Siberfx\Turnstile\Http\Middleware\VerifyTurnstile;
use Siberfx\Turnstile\Rules\Turnstile as TurnstileRule;
use Siberfx\Turnstile\View\Components\Turnstile as TurnstileComponent;

class TurnstileServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/turnstile.php', 'turnstile');

        // Scoped so Octane workers start every request with a clean token cache.
        $this->app->scoped(Turnstile::class, static fn (Application $app): Turnstile => new Turnstile(
            $app->make(HttpFactory::class),
            (array) $app->make('config')->get('turnstile', []),
        ));

        $this->app->alias(Turnstile::class, 'turnstile');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'turnstile');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'turnstile');

        Blade::component('turnstile', TurnstileComponent::class);

        Blade::directive('turnstileScripts', static fn (): string => '<script src="<?php echo e(app(\\'.Turnstile::class.'::class)->scriptUrl()); ?>" async defer></script>');

        Rule::macro('turnstile', fn (?string $action = null): TurnstileRule => new TurnstileRule($action));

        $this->callAfterResolving('router', static function (Router $router): void {
            $router->aliasMiddleware('turnstile', VerifyTurnstile::class);
        });

        if ($this->app->runningInConsole()) {
            // Each group also carries the shared "turnstile" tag used by turnstile:publish.
            $this->publishes([
                __DIR__.'/../config/turnstile.php' => config_path('turnstile.php'),
            ], ['turnstile', 'turnstile-config']);

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/turnstile'),
            ], ['turnstile', 'turnstile-views']);

            $this->publishes([
                __DIR__.'/../lang' => $this->app->langPath('vendor/turnstile'),
            ], ['turnstile', 'turnstile-lang']);

            $this->commands([PublishCommand::class]);
        }
    }
}
