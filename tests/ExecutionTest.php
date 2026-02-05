<?php

namespace Spatie\WebTinker\Tests;

use Spatie\WebTinker\Tinker;

class ExecutionTest extends TestCase
{
    /** @var \Spatie\WebTinker\Tinker */
    private $tinker;

    public function setUp(): void
    {
        parent::setUp();

        $this->tinker = app(Tinker::class);
    }

    /** @test */
    public function it_can_execute_php_code()
    {
        $output = $this->tinker->execute('echo "hello world";');

        $this->assertStringContainsString('hello world', $output);
    }

    /** @test */
    public function it_can_return_a_value()
    {
        $output = $this->tinker->execute('return 1 + 1;');

        $this->assertStringContainsString('2', $output);
    }
}
