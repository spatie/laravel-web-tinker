<?php

namespace Spatie\WebTinker;

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Psy\Completion\CompletionEngine;
use Psy\Context;
use Spatie\WebTinker\Completion\ClassIndex;
use Spatie\WebTinker\Completion\Completer;
use Spatie\WebTinker\Completion\CompletionLabeler;
use Spatie\WebTinker\Completion\Source\AnalysisCapture;
use Spatie\WebTinker\Completion\Source\ClassIndexSource;
use Spatie\WebTinker\Completion\Source\StaticTypeSource;
use Spatie\WebTinker\Completion\StringArgument\ConfigKeysSource;
use Spatie\WebTinker\Completion\StringArgument\EnvKeysSource;
use Spatie\WebTinker\Completion\TypeInference\DocBlockReader;
use Spatie\WebTinker\Completion\TypeInference\StaticTypeResolver;
use Spatie\WebTinker\Console\InstallCommand;
use Spatie\WebTinker\Http\Controllers\CompletionController;
use Spatie\WebTinker\Http\Controllers\WebTinkerController;
use Spatie\WebTinker\Http\Middleware\Authorize;
use Spatie\WebTinker\OutputModifiers\OutputModifier;

class WebTinkerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/web-tinker.php' => config_path('web-tinker.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../resources/views' => base_path('resources/views/vendor/web-tinker'),
            ], 'views');

            $this->publishes([
                __DIR__.'/../public' => public_path('vendor/web-tinker'),
            ], 'web-tinker-assets');
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'web-tinker');

        $this->app->bind(OutputModifier::class, config('web-tinker.output_modifier'));

        Route::middlewareGroup('web-tinker', config('web-tinker.middleware', []));

        $this
            ->registerRoutes()
            ->registerWebTinkerGate();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/web-tinker.php', 'web-tinker');

        $this->commands(InstallCommand::class);

        $this->registerCompleter();
    }

    protected function registerCompleter(): self
    {
        $this->app->singleton(DocBlockReader::class);

        $this->app->singleton(ClassIndex::class, function () {
            return new ClassIndex(base_path(), (int) config('web-tinker.completion.cache_ttl', 300));
        });

        $this->app->singleton(StaticTypeResolver::class, function ($app) {
            return new StaticTypeResolver(
                $app->make(ClassIndex::class),
                $app->make(DocBlockReader::class)
            );
        });

        $this->app->singleton(AnalysisCapture::class);

        $this->app->singleton(CompletionEngine::class, function ($app) {
            // An empty context, because there is nothing in it: each request
            // gets a fresh shell that has evaluated nothing. StaticTypeSource
            // is what stands in for the runtime types the shell would
            // otherwise supply.
            $engine = new CompletionEngine(new Context());

            $engine->addSource($app->make(StaticTypeSource::class));

            $engine->registerDefaultSources([
                new ClassIndexSource(
                    $app->make(ClassIndex::class),
                    (int) config('web-tinker.completion.limit', 100)
                ),
            ]);

            // Last, so the analysis it keeps is the one every other source saw.
            $engine->addSource($app->make(AnalysisCapture::class));

            return $engine;
        });

        $this->app->singleton(Completer::class, function ($app) {
            return new Completer(
                $app->make(CompletionEngine::class),
                $app->make(CompletionLabeler::class),
                $app->make(AnalysisCapture::class),
                [new EnvKeysSource(base_path()), new ConfigKeysSource()],
                (int) config('web-tinker.completion.limit', 100)
            );
        });

        return $this;
    }

    protected function routeConfiguration()
    {
        return [
            'prefix' => config('web-tinker.path'),
            'middleware' => 'web-tinker'
        ];
    }

    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            Route::get('/', [WebTinkerController::class, 'index']);
            Route::post('/', [WebTinkerController::class, 'execute']);
            Route::post('completions', CompletionController::class);
        });

        return $this;
    }

    protected function registerWebTinkerGate()
    {
        Gate::define('viewWebTinker', function ($user = null) {
            return app()->environment('local');
        });

        return $this;
    }
}
