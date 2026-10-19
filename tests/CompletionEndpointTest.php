<?php

namespace Spatie\WebTinker\Tests;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Spatie\WebTinker\Completion\ClassIndex;

class CompletionEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(new User);

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

    #[Test]
    public function it_protects_completions_with_the_web_tinker_gate(): void
    {
        Gate::define('viewWebTinker', fn () => false);

        $this->postJson('/tinker/completions', ['code' => 'new Tinke', 'cursor' => 9])
            ->assertForbidden();
    }

    #[Test]
    public function it_does_not_expose_completions_when_web_tinker_is_disabled(): void
    {
        config()->set('web-tinker.enabled', false);

        $this->postJson('/tinker/completions', ['code' => 'new Tinke', 'cursor' => 9])
            ->assertForbidden();
    }

    #[Test]
    public function it_accepts_an_empty_editor(): void
    {
        $this->postJson('/tinker/completions', ['code' => '', 'cursor' => 0])
            ->assertOk()
            ->assertExactJson(['from' => 0, 'completions' => []]);
    }

    #[Test]
    public function it_preserves_indentation_when_calculating_replacement_offsets(): void
    {
        $code = "\n    config('web-tinker.compl";

        $this->postJson('/tinker/completions', ['code' => $code, 'cursor' => mb_strlen($code)])
            ->assertOk()
            ->assertJsonPath('from', 13)
            ->assertJsonFragment(['value' => 'web-tinker.completion.enabled']);
    }
}
