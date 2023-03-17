<?php

namespace Spatie\WebTinker\Tests;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Spatie\WebTinker\Http\Middleware\Authorize;

//class AuthorizeTest extends TestCase
//{
//    public function setUp(): void
//    {
//        parent::setUp();
//
//        $this->actingAs(new User());
//
//        config()->set('web-tinker.enabled', true);
//
//        app()->detectEnvironment(function () {
//            return 'local';
//        });
//
//        Route::get('/test', function () {
//            return 'ok';
//        })->middleware(Authorize::class);
//    }
//
//    /** @test */
//    public function it_will_allow_requests_if_it_is_enabled()
//    {
//        $this->get('/test')->assertOk();
//    }
//
//
//    /** @test */
//    public function it_will_not_allow_requests_if_the_gate_does_not_allow_it()
//    {
//        Gate::define('viewWebTinker', function () {
//            return false;
//        });
//
//        $this->get('/test')->assertStatus(403);
//    }
//
//    /** @test */
//    public function it_will_not_allow_requests_if_it_is_disabled_even_if_the_gate_allows_it()
//    {
//        Gate::define('viewWebTinker', function () {
//            return true;
//        });
//
//        config()->set('web-tinker.enabled', false);
//
//        $this->get('/test')->assertStatus(403);
//    }
//}
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
