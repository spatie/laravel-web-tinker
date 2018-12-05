<?php

namespace Spatie\WebTinker\Tests;

use Spatie\WebTinker\Tinker;

class TinkerTest extends TestCase
{
    /** @test */
    public function it_can_execute_some_code()
    {
        $output = Tinker::output('$a = 1;');

        dd($output);
    }
}