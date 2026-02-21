<?php

namespace Spatie\WebTinker\Tests;

use Psy\Configuration;
use Psy\Shell;
use Spatie\WebTinker\Tinker;
use PHPUnit\Framework\Attributes\Test;

class ExecutionTest extends TestCase
{
    /** @var \Spatie\WebTinker\Tinker */
    private $tinker;

    public function setUp(): void
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
        $shellProperty = new \ReflectionProperty(Tinker::class, 'shell');
        $shellProperty->setAccessible(true);
        $shell = $shellProperty->getValue($this->tinker);

        $configProperty = new \ReflectionProperty(Shell::class, 'config');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($shell);

        $this->assertSame(Configuration::INTERACTIVE_MODE_DISABLED, $config->interactiveMode());
        $this->assertFalse($config->getInputInteractive());
    }
}
