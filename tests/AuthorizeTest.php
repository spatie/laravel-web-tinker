<?php

namespace Spatie\WebTinker\Tests;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Spatie\WebTinker\Http\Middleware\Authorize;

beforeEach(function () {

    $this->actingAs(new User());

    config()->set('web-tinker.enabled', true);

    app()->detectEnvironment(function () {
        return 'local';
    });

    Route::get('/test', function () {
        return 'ok';
    })->middleware(Authorize::class);
});

it('will allow request if it is enable', function () {

    $this->get('/test')->assertOk();
});
it('will not allow requests if the gate does not allow it', function(){

    Gate::define('viewWebTinker', function () {
        return false;
    });

    $this->get('/test')->assertStatus(403);
});
it('will not allow requests if it is disabled even if the gate allows it', function (){

    Gate::define('viewWebTinker', function () {
        return true;
    });

    config()->set('web-tinker.enabled', false);

    $this->get('/test')->assertStatus(403);
});
