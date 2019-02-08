<?php

namespace Spatie\WebTinker\Tests;

use Spatie\WebTinker\WebTinkerServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTest;

class TestCase extends OrchestraTest
{
    protected function getPackageProviders($app)
    {
        return [WebTinkerServiceProvider::class];
    }
}
