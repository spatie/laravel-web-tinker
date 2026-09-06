<?php

namespace Spatie\WebTinker\Tests;

use PHPUnit\Framework\Attributes\Test;
use Spatie\WebTinker\Completion\ClassIndex;
use Spatie\WebTinker\Completion\Completer;
use Spatie\WebTinker\Tests\Fixtures\DocumentedModel;

class DocBlockCompletionTest extends TestCase
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

    private function model(string $tail): string
    {
        return '$model = new \\'.DocumentedModel::class.'(); $model->'.$tail;
    }

        #[Test]
    public function it_completes_a_property_that_only_exists_in_the_docblock()
    {
        $this->assertContains('email', $this->values($this->model('ema')));
    }

        #[Test]
    public function it_completes_a_property_read_annotation()
    {
        $this->assertContains('widget_count', $this->values($this->model('widget_c')));
    }

        #[Test]
    public function a_documented_property_outranks_a_method_of_the_same_name()
    {
        // On a model a relation is declared as `widgets()` and documented as
        // `$widgets`; the two return different things, and the annotation says
        // which one is meant.
        $result = $this->completer->complete($code = $this->model('widget'), strlen($code));

        $widgets = array_values(array_filter(
            $result->completions,
            fn ($completion) => $completion->value === 'widgets'
        ));

        $this->assertCount(1, $widgets);
        $this->assertSame('property', $widgets[0]->meta);
    }

        #[Test]
    public function it_completes_a_method_that_only_exists_in_the_docblock()
    {
        $this->assertContains('findByEmail', $this->values($this->model('findByE')));
    }

        #[Test]
    public function it_labels_a_documented_property_as_a_property()
    {
        $result = $this->completer->complete($code = $this->model('ema'), strlen($code));

        $metas = array_unique(array_map(fn ($completion) => $completion->meta, $result->completions));

        $this->assertSame(['property'], $metas);
    }

        #[Test]
    public function it_resolves_a_documented_type_through_the_use_statements_of_its_file()
    {
        // `Widget[]|Collection` has to become Eloquent's Collection — the one
        // this fixture imports — for `->fir` to offer anything at all.
        $this->assertContains('first', $this->values($this->model('widgets->fir')));
    }

        #[Test]
    public function it_completes_straight_off_a_static_call()
    {
        $values = $this->values('\\'.DocumentedModel::class.'::getById(1)->ema');

        $this->assertContains('email', $values);
    }

        #[Test]
    public function it_completes_a_chain_that_starts_from_a_static_call()
    {
        $values = $this->values('\\'.DocumentedModel::class.'::getById(1)->widgets->fir');

        $this->assertContains('first', $values);
    }

        #[Test]
    public function it_stays_quiet_for_a_static_method_with_no_declared_or_annotated_type()
    {
        $this->assertSame([], $this->values('\\'.DocumentedModel::class.'::mystery(1)->ema'));
    }

        #[Test]
    public function it_follows_an_element_out_of_a_documented_collection()
    {
        // The full shape from a real snippet: a relation, one item out of it,
        // then that item's own members.
        $code = $this->model('widgets->first()').'; $widget = $model->widgets->first(); $widget->sp';

        $this->assertContains('spin', $this->values($code));
    }

        #[Test]
    public function it_reads_the_element_type_out_of_a_generic_annotation()
    {
        $values = $this->values($this->model('gadgets()->first()->sp'));

        $this->assertContains('spin', $values);
    }

        #[Test]
    public function it_lists_reflected_and_documented_members_together_without_duplicates()
    {
        $values = $this->values($this->model(''));

        $this->assertContains('email', $values);
        $this->assertContains('save', $values);
        $this->assertSame(count($values), count(array_unique($values)));
    }
}
