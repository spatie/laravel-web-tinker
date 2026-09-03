<?php

namespace Spatie\WebTinker\Tests;

use Illuminate\Foundation\Auth\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\WebTinker\Completion\ClassIndex;

class CompletionEndpointTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->actingAs(new User());

        config()->set('web-tinker.enabled', true);

        app()->detectEnvironment(fn () => 'local');

        app(ClassIndex::class)->flush();
    }

    protected function getEnvironmentSetUp($app)
    {
        $app->setBasePath(realpath(__DIR__.'/..'));

        // The routes run behind EncryptCookies, which needs a key.
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

        #[Test]
    public function it_serves_completions_as_json()
    {
        $this
            ->postJson('/tinker/completions', ['code' => 'new Tinke', 'cursor' => 9])
            ->assertOk()
            ->assertJsonStructure([
                'from',
                'completions' => [['value', 'label', 'meta']],
            ]);
    }

        #[Test]
    public function it_requires_a_cursor_position()
    {
        $this
            ->postJson('/tinker/completions', ['code' => 'new Tinke'])
            ->assertStatus(422);
    }

        #[Test]
    public function it_returns_nothing_when_completion_is_switched_off()
    {
        config()->set('web-tinker.completion.enabled', false);

        $this
            ->postJson('/tinker/completions', ['code' => 'new Tinke', 'cursor' => 9])
            ->assertOk()
            ->assertJson(['completions' => []]);
    }
}
