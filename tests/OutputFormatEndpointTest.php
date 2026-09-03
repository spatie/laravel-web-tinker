<?php

namespace Spatie\WebTinker\Tests;

use Illuminate\Foundation\Auth\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\WebTinker\Http\Controllers\WebTinkerController;
use Spatie\WebTinker\Output\DumpRenderer;

class OutputFormatEndpointTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(new User());

        config()->set('web-tinker.enabled', true);

        app()->detectEnvironment(fn () => 'local');
    }

    protected function getEnvironmentSetUp($app)
    {
        // The routes run behind EncryptCookies, which needs a key.
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

        #[Test]
    public function it_executes_code_as_text_by_default()
    {
        $response = $this->post('/tinker', ['code' => 'return 1 + 1;']);

        $response->assertOk();

        $this->assertStringNotContainsString('sf-dump', $response->getContent());
    }

        #[Test]
    public function it_executes_code_as_html_when_asked_to()
    {
        $response = $this->post('/tinker', ['code' => 'return 1 + 1;', 'format' => 'html']);

        $response->assertOk();

        $this->assertStringContainsString('sf-dump', $response->getContent());
    }

        #[Test]
    public function it_rejects_an_unknown_format()
    {
        $this
            ->postJson('/tinker', ['code' => 'return 1;', 'format' => 'yaml'])
            ->assertStatus(422);
    }

        #[Test]
    public function it_falls_back_to_text_when_rich_output_is_switched_off()
    {
        config()->set('web-tinker.dump.enabled', false);

        $response = $this->post('/tinker', ['code' => 'return 1 + 1;', 'format' => 'html']);

        $response->assertOk();

        $this->assertStringNotContainsString('sf-dump', $response->getContent());
        $this->assertStringContainsString('2', $response->getContent());
    }

        #[Test]
    public function it_asks_the_page_for_rich_output_when_it_is_switched_on()
    {
        $data = $this->page();

        $this->assertSame('html', $data['outputFormat']);
        $this->assertStringContainsString('Sfdump', $data['dumpAssets']);
    }

        #[Test]
    public function it_leaves_the_dumper_assets_out_of_the_page_when_rich_output_is_switched_off()
    {
        config()->set('web-tinker.dump.enabled', false);

        $data = $this->page();

        $this->assertSame('text', $data['outputFormat']);
        $this->assertSame('', $data['dumpAssets']);
    }

    /**
     * The view is inspected rather than rendered: rendering needs the
     * package's published mix manifest, which a testbench app has not got.
     */
    private function page(): array
    {
        return app(WebTinkerController::class)
            ->index(app(DumpRenderer::class))
            ->getData();
    }
}
