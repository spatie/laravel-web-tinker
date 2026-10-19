<?php

namespace Spatie\WebTinker\Tests;

use PHPUnit\Framework\Attributes\Test;
use Psy\Configuration;
use Psy\Shell;
use ReflectionProperty;
use Spatie\WebTinker\Tinker;

class ExecutionTest extends TestCase
{
    /** @var Tinker */
    private $tinker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tinker = app(Tinker::class);
    }

    #[Test]
    public function it_can_execute_php_code()
    {
        $output = $this->tinker->execute('echo "hello world";');

        $this->assertStringContainsString('hello world', $output);
    }

    #[Test]
    public function it_can_return_a_value()
    {
        $output = $this->tinker->execute('return 1 + 1;');

        $this->assertStringContainsString('2', $output);
    }

    #[Test]
    public function it_runs_psysh_in_non_interactive_mode()
    {
        $shellProperty = new ReflectionProperty(Tinker::class, 'shell');
        $shell = $shellProperty->getValue($this->tinker);

        $configProperty = new ReflectionProperty(Shell::class, 'config');
        $config = $configProperty->getValue($shell);

        $this->assertSame(Configuration::INTERACTIVE_MODE_DISABLED, $config->interactiveMode());
        $this->assertFalse($config->getInputInteractive());
    }
}
