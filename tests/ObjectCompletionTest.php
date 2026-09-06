<?php

namespace Spatie\WebTinker\Tests;

use Illuminate\Foundation\Auth\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\WebTinker\Completion\ClassIndex;
use Spatie\WebTinker\Completion\Completer;

class ObjectCompletionTest extends TestCase
{
    private Completer $completer;

    public function setUp(): void
    {
        parent::setUp();

        app(ClassIndex::class)->flush();

        $this->completer = app(Completer::class);
    }

    protected function getEnvironmentSetUp($app)
    {
        $app->setBasePath(realpath(__DIR__.'/..'));
    }

    private function values(string $code): array
    {
        $result = $this->completer->complete($code, strlen($code));

        return array_map(fn ($completion) => $completion->value, $result->completions);
    }

        #[Test]
    public function it_completes_members_of_a_constructed_object()
    {
        $values = $this->values('$date = new \DateTime(); $date->for');

        $this->assertContains('format', $values);
    }

        #[Test]
    public function it_completes_members_of_a_model_returned_by_a_static_factory()
    {
        // The shape from the README: assign from a query, complete on the result.
        $values = $this->values('$user = '.User::class.'::first(); $user->sav');

        $this->assertContains('save', $values);
    }

        #[Test]
    public function it_resolves_a_class_written_by_its_short_name()
    {
        $values = $this->values('$tinker = new Tinker(); $tinker->exec');

        $this->assertContains('execute', $values);
    }

        #[Test]
    public function it_honours_a_var_annotation()
    {
        $values = $this->values('/** @var \DateTime $moment */'."\n".'$moment->for');

        $this->assertContains('format', $values);
    }

        #[Test]
    public function it_resolves_a_class_pulled_from_the_container()
    {
        $values = $this->values('$tinker = app(\Spatie\WebTinker\Tinker::class); $tinker->exec');

        $this->assertContains('execute', $values);
    }

        #[Test]
    public function it_follows_a_method_chain()
    {
        // Collection::filter() is typed `@return static` and nothing else,
        // which is the shape most of Laravel's fluent API takes.
        $values = $this->values('$items = new \Illuminate\Support\Collection([1, 2]); $items->filter()->fir');

        $this->assertContains('first', $values);
    }

        #[Test]
    public function it_uses_the_last_assignment_to_a_variable()
    {
        $values = $this->values('$thing = new \DateTime(); $thing = new \ArrayObject(); $thing->');

        $this->assertContains('count', $values);
        $this->assertNotContains('format', $values);
    }

        #[Test]
    public function it_lists_every_member_when_nothing_has_been_typed_after_the_arrow()
    {
        $values = $this->values('$date = new \DateTime(); $date->');

        $this->assertContains('format', $values);
        $this->assertContains('modify', $values);
    }

        #[Test]
    public function it_labels_members_by_kind()
    {
        $result = $this->completer->complete(
            $code = '$exception = new \Exception("x"); $exception->getM',
            strlen($code)
        );

        $metas = array_unique(array_map(fn ($completion) => $completion->meta, $result->completions));

        $this->assertSame(['method'], $metas);
    }

        #[Test]
    public function it_stays_quiet_when_the_type_cannot_be_established()
    {
        $this->assertSame([], $this->values('$mystery->doSomething'));
    }

        #[Test]
    public function it_replaces_only_the_member_being_typed()
    {
        $code = '$date = new \DateTime(); $date->for';

        $result = $this->completer->complete($code, strlen($code));

        $this->assertSame(strlen($code) - strlen('for'), $result->from);
    }

        #[Test]
    public function it_does_not_execute_the_snippet_while_resolving_a_type()
    {
        $code = '$boom = '.ExplodingFactory::class.'::make(); $boom->any';

        $this->values($code);

        $this->assertFalse(ExplodingFactory::$wasCalled);
    }
}

class ExplodingFactory
{
    public static bool $wasCalled = false;

    public static function make(): self
    {
        static::$wasCalled = true;

        return new self();
    }

    public function anything(): void
    {
    }
}
