<?php

namespace Spatie\WebTinker\Tests;

use PHPUnit\Framework\Attributes\Test;
use Spatie\WebTinker\Completion\ClassIndex;
use Spatie\WebTinker\Completion\Completer;
use Spatie\WebTinker\Completion\CompletionResult;

class CompletionTest extends TestCase
{
    private Completer $completer;

    public function setUp(): void
    {
        parent::setUp();

        // The class index memoizes across instances; a stale one would make
        // these assertions depend on test order.
        app(ClassIndex::class)->flush();

        $this->completer = app(Completer::class);
    }

    protected function getEnvironmentSetUp($app)
    {
        // Index this package's own source, which is the only tree guaranteed
        // to exist while the test suite runs.
        $app->setBasePath(realpath(__DIR__.'/..'));
    }

    private function complete(string $code): CompletionResult
    {
        return $this->completer->complete($code, strlen($code));
    }

    private function values(CompletionResult $result): array
    {
        return array_map(fn ($completion) => $completion->value, $result->completions);
    }

        #[Test]
    public function it_completes_a_class_by_its_short_name()
    {
        $values = $this->values($this->complete('$tinker = new Tinke'));

        $this->assertContains('Spatie\WebTinker\Tinker', $values);
    }

        #[Test]
    public function it_completes_a_class_by_its_fully_qualified_name()
    {
        $values = $this->values($this->complete('Spatie\WebTinker\Completion\Comp'));

        $this->assertContains('Spatie\WebTinker\Completion\Completer', $values);
    }

        #[Test]
    public function it_labels_class_completions()
    {
        $result = $this->complete('new Tinke');

        $metas = array_unique(array_map(fn ($completion) => $completion->meta, $result->completions));

        $this->assertSame(['class'], $metas);
    }

        #[Test]
    public function it_reports_where_the_replacement_starts()
    {
        $result = $this->complete('$foo = new Tinke');

        $this->assertSame(strlen('$foo = new '), $result->from);
    }

        #[Test]
    public function it_completes_environment_variable_names()
    {
        $_ENV['WEB_TINKER_TEST_KEY'] = 'secret-value';

        $result = $this->complete('env("WEB_TINKER_TEST');

        $this->assertContains('WEB_TINKER_TEST_KEY', $this->values($result));
        $this->assertSame(strlen('env("'), $result->from);

        unset($_ENV['WEB_TINKER_TEST_KEY']);
    }

        #[Test]
    public function it_never_exposes_environment_values()
    {
        $_ENV['WEB_TINKER_TEST_KEY'] = 'secret-value';

        $result = $this->complete('env("WEB_TINKER_TEST');

        $this->assertStringNotContainsString('secret-value', json_encode($result->toArray()));

        unset($_ENV['WEB_TINKER_TEST_KEY']);
    }

        #[Test]
    public function it_completes_configuration_keys()
    {
        config()->set('web-tinker.completion.enabled', true);

        $values = $this->values($this->complete("config('web-tinker.compl"));

        $this->assertContains('web-tinker.completion.enabled', $values);
    }

        #[Test]
    public function it_offers_configuration_groups_as_well_as_leaves()
    {
        $values = $this->values($this->complete("config('web-tinker.comple"));

        $this->assertContains('web-tinker.completion', $values);
    }

        #[Test]
    public function a_string_argument_matcher_suppresses_the_token_based_matchers()
    {
        $_ENV['APP_WEB_TINKER_MARKER'] = '1';

        $metas = array_unique(array_map(
            fn ($completion) => $completion->meta,
            $this->complete('env("APP_')->completions
        ));

        $this->assertSame(['env'], $metas);

        unset($_ENV['APP_WEB_TINKER_MARKER']);
    }

        #[Test]
    public function it_completes_static_members()
    {
        $values = $this->values($this->complete('Spatie\WebTinker\Completion\CompletionResult::em'));

        $this->assertContains('empty', $values);
    }

        #[Test]
    public function it_ranks_an_exactly_typed_class_name_first()
    {
        $values = $this->values($this->complete('new Completer'));

        $this->assertSame('Spatie\WebTinker\Completion\Completer', $values[0]);
    }

        #[Test]
    public function it_does_not_offer_functions_after_a_static_call()
    {
        $metas = array_map(
            fn ($completion) => $completion->meta,
            $this->complete('Spatie\WebTinker\Completion\CompletionResult::em')->completions
        );

        $this->assertNotContains('function', $metas);
        $this->assertNotContains('keyword', $metas);
    }

        #[Test]
    public function it_returns_nothing_when_there_is_nothing_to_complete()
    {
        $this->assertSame([], $this->complete('   ')->completions);
    }

        #[Test]
    public function it_respects_the_configured_limit()
    {
        config()->set('web-tinker.completion.limit', 3);

        app()->forgetInstance(Completer::class);

        $result = app(Completer::class)->complete('S', 1);

        $this->assertLessThanOrEqual(3, count($result->completions));
    }
}
