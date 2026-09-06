<?php

namespace Spatie\WebTinker\Completion;

use Psy\Completion\AnalysisResult;
use Psy\Completion\CompletionKind;
use ReflectionClass;
use Spatie\WebTinker\Completion\TypeInference\DocBlockReader;

/**
 * Works out what kind of thing each suggestion is, so the editor can show it.
 *
 * PsySH's engine returns plain strings, which is all readline can use. A list
 * in a browser has room to say whether `individuals` is a relation or a
 * method, and that is often the whole question.
 *
 * The kind is re-derived from what the name actually *is*, rather than from
 * which source produced it: sources are merged and de-duplicated before
 * anything gets here, and a name found twice should not be labelled by
 * whichever source happened to win.
 */
class CompletionLabeler
{
    public function __construct(protected DocBlockReader $docBlocks)
    {
    }

    /**
     * Label each suggestion, and put the documented ones first.
     *
     * Both jobs need the same reflection of the same types, and the second one
     * matters as much as the first: an Eloquent model documents a couple of
     * dozen columns and relations with `@property`, and inherits some two
     * hundred methods and a handful of framework properties that reflection
     * reports first. Left in that order the columns never appear.
     *
     * @param string[] $completions
     *
     * @return Completion[]
     */
    public function describe(array $completions, AnalysisResult $analysis): array
    {
        $reflections = $this->reflect($analysis->leftSideTypes);
        $documented = $this->documentedMembers($reflections);

        $described = array_map(
            fn (string $completion) => new Completion(
                $completion,
                $this->metaFor($completion, $analysis, $reflections)
            ),
            $completions
        );

        if ($documented === []) {
            return $described;
        }

        $first = [];
        $rest = [];

        foreach ($described as $completion) {
            if (isset($documented[$completion->value])) {
                $first[] = $completion;

                continue;
            }

            $rest[] = $completion;
        }

        return array_merge($first, $rest);
    }

    /**
     * Names a class describes in its docblock rather than declares in code.
     *
     * @param ReflectionClass[] $reflections
     *
     * @return array<string, true>
     */
    protected function documentedMembers(array $reflections): array
    {
        $names = [];

        foreach ($reflections as $reflection) {
            foreach (array_keys($this->docBlocks->properties($reflection)) as $property) {
                $names[$property] = true;
            }

            foreach ($this->docBlocks->methods($reflection) as $method) {
                $names[$method] = true;
            }
        }

        return $names;
    }

    /** @param ReflectionClass[] $reflections */
    protected function metaFor(string $completion, AnalysisResult $analysis, array $reflections): string
    {
        if ($reflections !== []) {
            return $this->memberMeta($completion, $reflections);
        }

        if (str_starts_with($completion, '$')) {
            return 'variable';
        }

        if ($this->isClassLike($completion)) {
            return 'class';
        }

        if (function_exists($completion)) {
            return 'function';
        }

        if (defined($completion)) {
            return 'constant';
        }

        return ($analysis->kinds & CompletionKind::KEYWORD) !== 0 ? 'keyword' : 'symbol';
    }

    /** @param ReflectionClass[] $reflections */
    protected function memberMeta(string $completion, array $reflections): string
    {
        foreach ($reflections as $reflection) {
            // A documented property wins over a method of the same name, and
            // on a model that is the common case: a relation is declared as
            // `individuals()` and documented as `$individuals`, and the two
            // return different things. The annotation is the author saying
            // which one is meant to be reached for — and it is the collection,
            // not the query builder behind it.
            if (isset($this->docBlocks->properties($reflection)[$completion])) {
                return 'property';
            }

            if ($reflection->hasMethod($completion) || in_array($completion, $this->docBlocks->methods($reflection), true)) {
                return 'method';
            }

            if ($reflection->hasConstant($completion)) {
                return 'constant';
            }
        }

        return 'property';
    }

    protected function isClassLike(string $name): bool
    {
        return class_exists($name) || interface_exists($name) || trait_exists($name);
    }

    /**
     * @param string[] $types
     *
     * @return ReflectionClass[]
     */
    protected function reflect(array $types): array
    {
        $reflections = [];

        foreach ($types as $type) {
            if (class_exists($type) || interface_exists($type)) {
                $reflections[] = new ReflectionClass($type);
            }
        }

        return $reflections;
    }
}
