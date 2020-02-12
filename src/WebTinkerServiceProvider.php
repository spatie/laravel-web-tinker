<?php

namespace Spatie\WebTinker;

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\WebTinker\Console\InstallCommand;
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

        $this
            ->registerRoutes()
            ->registerWebTinkerGate();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/web-tinker.php', 'web-tinker');

        $this->commands(InstallCommand::class);
    }

    protected function registerRoutes()
    {
        Route::prefix(config('web-tinker.path'))->middleware([
            EncryptCookies::class,
            StartSession::class,
            Authorize::class,
        ])->group(function () {
            Route::get('/', [WebTinkerController::class, 'index']);
            Route::post('/', [WebTinkerController::class, 'execute']);
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
