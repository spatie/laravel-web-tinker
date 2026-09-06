<?php

namespace Spatie\WebTinker\Completion\Source;

use Psy\Completion\AnalysisResult;
use Psy\Completion\CompletionKind;
use Psy\Completion\Source\SourceInterface;
use Spatie\WebTinker\Completion\TypeInference\StaticTypeResolver;

/**
 * Supplies the type of the expression left of the caret when the shell cannot.
 *
 * PsySH resolves `$user->` by looking `$user` up in the shell's context and
 * reflecting on the object it finds. Run from a browser there is no such
 * object: every request builds a fresh shell with an empty scope, so
 * `leftSideTypes` comes back empty and every member source downstream — object
 * methods and properties, magic methods and properties — has nothing to work
 * with.
 *
 * This fills that in from a static reading of the code. It contributes no
 * completions of its own; it is registered ahead of the member sources purely
 * so they see a type. The analysis object is shared across the pipeline, which
 * is what makes handing the answer forward possible.
 *
 * A type the shell *does* know is always left alone: a real object beats
 * anything inferred from the source.
 *
 * @see \Psy\Completion\CompletionEngine::collectFromSources()
 */
class StaticTypeSource implements SourceInterface
{
    /** Everything that needs to know the type of what it is reaching into. */
    protected const MEMBER_KINDS = CompletionKind::OBJECT_METHOD
        | CompletionKind::OBJECT_PROPERTY
        | CompletionKind::STATIC_METHOD
        | CompletionKind::STATIC_PROPERTY
        | CompletionKind::CLASS_CONSTANT;

    public function __construct(protected StaticTypeResolver $resolver)
    {
    }

    public function appliesToKind(int $kinds): bool
    {
        return ($kinds & self::MEMBER_KINDS) !== 0;
    }

    /** @return string[] always empty — this source answers with a type, not a name */
    public function getCompletions(AnalysisResult $analysis): array
    {
        if ($analysis->leftSideValue !== null || $this->hasUsableTypes($analysis)) {
            return [];
        }

        if ($analysis->leftSide === null) {
            return [];
        }

        $resolved = $analysis->leftSideNode !== null
            ? $this->resolver->resolveNode($analysis->leftSideNode, $analysis->input)
            : $this->resolver->resolveText($analysis->leftSide, $analysis->input);

        $analysis->leftSideTypes = $resolved->classes;

        return [];
    }

    /**
     * Whether the types already resolved name something that can be reflected
     * on.
     *
     * A type is only worth keeping if it loads: every source downstream turns
     * these into a `ReflectionClass` and quietly skips whatever does not. An
     * unloadable name is therefore no better than no answer, and standing back
     * for one would mean offering nothing at all.
     */
    protected function hasUsableTypes(AnalysisResult $analysis): bool
    {
        foreach ($analysis->leftSideTypes as $type) {
            if (\is_string($type) && (\class_exists($type) || \interface_exists($type))) {
                return true;
            }
        }

        return false;
    }
}
