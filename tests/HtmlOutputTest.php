<?php

namespace Spatie\WebTinker\Tests;

use PHPUnit\Framework\Attributes\Test;
use Spatie\WebTinker\Output\DumpRenderer;
use Spatie\WebTinker\Output\OutputFormat;
use Spatie\WebTinker\Tinker;

class HtmlOutputTest extends TestCase
{
    private Tinker $tinker;

    public function setUp(): void
    {
        parent::setUp();

        $this->tinker = app(Tinker::class);
    }

        #[Test]
    public function it_renders_a_return_value_as_var_dumper_markup()
    {
        $output = $this->tinker->execute('return ["answer" => 42];', OutputFormat::Html);

        $this->assertStringContainsString('sf-dump', $output);
        $this->assertStringContainsString('answer', $output);
        $this->assertStringContainsString('42', $output);
    }

        #[Test]
    public function it_still_returns_plain_text_by_default()
    {
        $output = $this->tinker->execute('return 1 + 1;');

        $this->assertStringContainsString('2', $output);
        $this->assertStringNotContainsString('sf-dump', $output);
    }

        #[Test]
    public function it_shows_echoed_output_above_the_returned_value()
    {
        $output = $this->tinker->execute('echo "written to stdout"; return 1;', OutputFormat::Html);

        $this->assertStringContainsString('wt-stdout', $output);
        $this->assertStringContainsString('written to stdout', $output);
        $this->assertStringContainsString('sf-dump', $output);
    }

        #[Test]
    public function it_escapes_echoed_output()
    {
        $output = $this->tinker->execute('echo "<script>alert(1)</script>";', OutputFormat::Html);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

        #[Test]
    public function it_renders_a_thrown_exception_instead_of_swallowing_it()
    {
        $output = $this->tinker->execute('throw new \RuntimeException("boom");', OutputFormat::Html);

        $this->assertStringContainsString('wt-exception-heading', $output);
        $this->assertStringContainsString('RuntimeException', $output);
        $this->assertStringContainsString('boom', $output);
    }

        #[Test]
    public function it_reports_an_exception_in_text_mode_too()
    {
        $output = $this->tinker->execute('throw new \RuntimeException("boom");');

        $this->assertStringContainsString('boom', $output);
    }

        #[Test]
    public function page_assets_carry_the_dumper_stylesheet_and_toggle_script()
    {
        $assets = app(DumpRenderer::class)->pageAssets();

        $this->assertStringContainsString('<style', $assets);
        $this->assertStringContainsString('Sfdump', $assets);
    }

        #[Test]
    public function individual_dumps_do_not_repeat_the_page_assets()
    {
        $output = $this->tinker->execute('return "hello";', OutputFormat::Html);

        $this->assertStringNotContainsString('<style', $output);
    }
}
