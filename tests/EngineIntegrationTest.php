<?php

namespace Spatie\WebTinker\Tests;

use PHPUnit\Framework\Attributes\Test;
use Psy\Completion\AnalysisResult;
use Psy\Completion\CompletionKind;
use Spatie\WebTinker\Completion\ClassIndex;
use Spatie\WebTinker\Completion\Completer;
use Spatie\WebTinker\Completion\Source\StaticTypeSource;
use Spatie\WebTinker\Tests\Fixtures\DocumentedModel;

/**
 * Behaviour that comes from sitting on PsySH's completion engine: a real
 * parser underneath, and our own static type source filling in for the runtime
 * context a web request does not have.
 */
class EngineIntegrationTest extends TestCase
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

    private function model(): string
    {
        return '\\'.DocumentedModel::class;
    }

        #[Test]
    public function it_resolves_a_receiver_holding_a_nested_call()
    {
        // The parser handles this for free. The regex grammar this replaced
        // gave up as soon as an argument contained parentheses.
        $values = $this->values($this->model().'::getById(intval("1"))->ema');

        $this->assertContains('email', $values);
    }

        #[Test]
    public function it_resolves_through_the_nullsafe_operator()
    {
        $values = $this->values('$moment = new \DateTime(); $moment?->for');

        $this->assertContains('format', $values);
    }

        #[Test]
    public function it_resolves_a_variable_assigned_on_an_earlier_line()
    {
        $values = $this->values("\$moment = new \\DateTime();\n\$other = 1;\n\$moment->for");

        $this->assertContains('format', $values);
    }

        #[Test]
    public function it_resolves_a_receiver_built_by_a_ternary()
    {
        $values = $this->values('$moment = true ? new \DateTime() : null; $moment->for');

        $this->assertContains('format', $values);
    }

        #[Test]
    public function it_leaves_a_type_the_shell_already_knows_alone()
    {
        $analysis = new AnalysisResult(
            CompletionKind::OBJECT_METHOD,
            'for',
            '$moment',
            ['DateTime']
        );

        app(StaticTypeSource::class)->getCompletions($analysis);

        $this->assertSame(['DateTime'], $analysis->leftSideTypes);
    }

        #[Test]
    public function it_replaces_a_type_that_names_nothing_loadable()
    {
        // PsySH mis-reads a generic annotation such as `Collection<int, Widget>`
        // as a class called "int, Widget". Nothing downstream can reflect on
        // that, so it is treated as no answer at all.
        $analysis = new AnalysisResult(
            CompletionKind::OBJECT_METHOD,
            'for',
            '$moment',
            ['int, Widget'],
            null,
            [],
            '$moment = new \DateTime(); $moment->for'
        );

        app(StaticTypeSource::class)->getCompletions($analysis);

        $this->assertSame(['DateTime'], $analysis->leftSideTypes);
    }

        #[Test]
    public function the_static_type_source_contributes_no_completions_of_its_own()
    {
        $analysis = new AnalysisResult(
            CompletionKind::OBJECT_METHOD,
            '',
            '$moment',
            [],
            null,
            [],
            '$moment = new \DateTime(); $moment->'
        );

        $this->assertSame([], app(StaticTypeSource::class)->getCompletions($analysis));
    }

        #[Test]
    public function it_offers_documented_members_before_inherited_ones()
    {
        // A model documents a couple of dozen columns and inherits hundreds of
        // methods; without ordering the columns never make the cut.
        $values = $this->values('$model = new '.$this->model().'(); $model->');

        $documented = array_slice($values, 0, 4);

        $this->assertContains('email', $documented);
        $this->assertContains('widgets', $documented);
    }

        #[Test]
    public function it_completes_a_chain_that_ends_in_a_collection_element()
    {
        $values = $this->values($this->model().'::getById(1)->gadgets()->first()->sp');

        $this->assertContains('spin', $values);
    }
}
