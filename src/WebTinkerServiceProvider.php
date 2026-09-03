<?php

namespace Spatie\WebTinker;

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Psy\TabCompletion\Matcher as PsyshMatchers;
use Spatie\WebTinker\Completion\ClassIndex;
use Spatie\WebTinker\Completion\Completer;
use Spatie\WebTinker\Completion\Matchers\ClassNamesMatcher;
use Spatie\WebTinker\Completion\Matchers\ConfigKeysMatcher;
use Spatie\WebTinker\Completion\Matchers\EnvKeysMatcher;
use Spatie\WebTinker\Completion\Matchers\ObjectMembersMatcher;
use Spatie\WebTinker\Completion\Matchers\PsyshMatcher;
use Spatie\WebTinker\Completion\TypeInference\DocBlockReader;
use Spatie\WebTinker\Completion\TypeInference\VariableTypeResolver;
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
        $this->app->singleton(ClassIndex::class, function () {
            return new ClassIndex(base_path(), (int) config('web-tinker.completion.cache_ttl', 300));
        });

        $this->app->singleton(DocBlockReader::class);

        $this->app->singleton(VariableTypeResolver::class, function ($app) {
            return new VariableTypeResolver(
                $app->make(ClassIndex::class),
                $app->make(DocBlockReader::class)
            );
        });

        $this->app->singleton(Completer::class, function ($app) {
            return new Completer(
                $this->completionMatchers($app->make(ClassIndex::class), $app->make(VariableTypeResolver::class)),
                (int) config('web-tinker.completion.limit', 100)
            );
        });

        return $this;
    }

    /**
     * Registration order is significant: the first matcher to claim a
     * suggestion decides how it is labelled, and the exclusive string matchers
     * come first so a half-typed `env("APP_` is not drowned in keywords.
     *
     * @return \Spatie\WebTinker\Completion\Matchers\Matcher[]
     */
    protected function completionMatchers(ClassIndex $classIndex, VariableTypeResolver $typeResolver): array
    {
        return [
            new ObjectMembersMatcher($typeResolver),
            new EnvKeysMatcher(base_path()),
            new ConfigKeysMatcher(),
            new ClassNamesMatcher($classIndex, (int) config('web-tinker.completion.limit', 100)),
            new PsyshMatcher(new PsyshMatchers\ClassMethodsMatcher(), 'method', true),
            new PsyshMatcher(new PsyshMatchers\ClassAttributesMatcher(), 'property', true),
            new PsyshMatcher(new PsyshMatchers\FunctionsMatcher(), 'function'),
            new PsyshMatcher(new PsyshMatchers\ConstantsMatcher(), 'constant'),
            new PsyshMatcher(new PsyshMatchers\KeywordsMatcher(), 'keyword'),
        ];
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
