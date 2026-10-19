<?php

namespace Spatie\WebTinker\Tests;

use PHPUnit\Framework\Attributes\Test;
use Spatie\WebTinker\Output\DumpRenderer;
use Spatie\WebTinker\Output\OutputFormat;
use Spatie\WebTinker\Tinker;

class HtmlOutputTest extends TestCase
{
    private Tinker $tinker;

    protected function setUp(): void
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
        $this->assertStringNotContainsString('<script', $output);
    }

    public function test_html_output_preserves_literal_markup_and_whitespace_before_an_exception(): void
    {
        $output = $this->tinker->execute('echo "  <aside>keep me</aside> <whisper>also me</whisper>  "; throw new \\RuntimeException("<script>boom</script>");', OutputFormat::Html);

        $this->assertStringContainsString('<pre class="wt-stdout">  &lt;aside&gt;keep me&lt;/aside&gt; &lt;whisper&gt;also me&lt;/whisper&gt;  ', $output);
        $this->assertStringContainsString('&lt;script&gt;boom&lt;/script&gt;', $output);
        $this->assertLessThan(strpos($output, 'wt-exception-heading'), strpos($output, 'wt-stdout'));

        $nextOutput = $this->tinker->execute('return "next";', OutputFormat::Html);

        $this->assertStringNotContainsString('keep me', $nextOutput);
        $this->assertStringContainsString('next', $nextOutput);
    }

    public function test_dump_limits_apply_to_flat_arrays_nested_values_and_strings(): void
    {
        config()->set('web-tinker.dump.max_items', 3);
        config()->set('web-tinker.dump.max_depth', 1);
        config()->set('web-tinker.dump.max_string_length', 4);
        app()->forgetInstance(DumpRenderer::class);
        $tinker = app(Tinker::class);

        $flat = $tinker->execute('return ["first", "second", "third", "must-not-appear"];', OutputFormat::Html);
        $nested = $tinker->execute('return ["child" => ["hidden" => "deep-value"]];', OutputFormat::Html);
        $string = $tinker->execute('return "abcdefghij";', OutputFormat::Html);

        $this->assertStringContainsString('firs', $flat);
        $this->assertStringContainsString('thir', $flat);
        $this->assertStringNotContainsString('must', $flat);
        $this->assertStringContainsString('child', $nested);
        $this->assertStringNotContainsString('hidden', $nested);
        $this->assertStringContainsString('abcd', $string);
        $this->assertStringNotContainsString('efghij', $string);
    }

    public function test_collections_and_models_use_laravel_casters(): void
    {
        $output = $this->tinker->execute('return collect([(new \\Illuminate\\Foundation\\Auth\\User)->setHidden(["password"])->forceFill(["name" => "Ada", "password" => "secret-password"])]);', OutputFormat::Html);

        $this->assertStringContainsString('Ada', $output);
        $this->assertStringContainsString('sf-dump-protected', $output);
        $this->assertStringNotContainsString('connection', $output);
    }
}
