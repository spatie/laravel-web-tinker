<?php

namespace Spatie\WebTinker\Tests;

use Orchestra\Testbench\TestCase as OrchestraTest;
use Spatie\WebTinker\WebTinkerServiceProvider;

class TestCase extends OrchestraTest
{
    protected function getPackageProviders($app)
    {
        return [WebTinkerServiceProvider::class];
    }
}
