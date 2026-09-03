<?php

namespace Spatie\WebTinker\Completion\Matchers;

use ReflectionMethod;
use ReflectionProperty;
use Spatie\WebTinker\Completion\Completion;
use Spatie\WebTinker\Completion\CompletionContext;
use Spatie\WebTinker\Completion\TypeInference\Expression;
use Spatie\WebTinker\Completion\TypeInference\VariableTypeResolver;

/**
 * Completes the members of an object: `$user->firstNa…`.
 *
 * The type behind the variable comes from {@see VariableTypeResolver}, which
 * reads the assignment rather than executing it — so
 *
 *   $user = User::first();
 *   $user->…
 *
 * offers the model's methods without a query ever running. The receiver can be
 * a chain, and can start from a static call directly —
 * `Company::getById(1)->individuals->…`.
 *
 * Suggestions come from reflection *and* from the class docblock, because an
 * Eloquent model's columns and relations only exist at runtime. When the type
 * cannot be established the matcher stays quiet: it is exclusive, so an
 * unresolvable receiver returns nothing rather than a list of PHP keywords
 * that could not follow an arrow anyway.
 */
class ObjectMembersMatcher extends BaseMatcher
{
    public function __construct(protected VariableTypeResolver $resolver)
    {
    }

    public function matches(CompletionContext $context): bool
    {
        return $this->parse($context) !== null;
    }

    public function complete(CompletionContext $context): array
    {
        [$receiver, $partial] = $this->parse($context) ?? [null, ''];

        if ($receiver === null) {
            return [];
        }

        $reflection = $this->resolver->reflect(
            $this->resolver->resolveExpression($receiver, $context->upToCursor())
        );

        if ($reflection === null) {
            return [];
        }

        $completions = [];

        // Documented members come first: on a model they are the columns and
        // relations, which is what someone is reaching for, and they would
        // otherwise fall off the end of a list of 200 inherited Eloquent
        // methods when nothing has been typed yet.
        foreach ($this->resolver->documentedProperties($reflection) as $property) {
            $completions[] = new Completion($property, 'property');
        }

        foreach ($this->resolver->documentedMethods($reflection) as $method) {
            $completions[] = new Completion($method, 'method', $method.'()');
        }

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $completions[] = new Completion($property->getName(), 'property');
        }

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (str_starts_with($method->getName(), '__')) {
                continue;
            }

            $completions[] = new Completion($method->getName(), 'method', $method->getName().'()');
        }

        $matching = array_filter(
            $completions,
            fn (Completion $completion) => $partial === ''
                || str_starts_with(mb_strtolower($completion->value), mb_strtolower($partial))
        );

        // Reflection and the docblock overlap on accessors; keep one of each name.
        $unique = [];

        foreach ($matching as $completion) {
            $unique[$completion->value] ??= $completion;
        }

        return array_values($unique);
    }

    public function replaceFrom(CompletionContext $context): int
    {
        [, $partial] = $this->parse($context) ?? [null, ''];

        return $context->cursor - strlen($partial);
    }

    public function isExclusive(): bool
    {
        return true;
    }

    /**
     * Split the text before the caret into the receiver expression and the
     * member typed so far, or null when the caret is not after an arrow.
     *
     * @return array{0: string, 1: string}|null
     */
    protected function parse(CompletionContext $context): ?array
    {
        $pattern = '/('.Expression::RECEIVER.')\s*->\s*(\w*)$/';

        if (! preg_match($pattern, $context->upToCursor(), $matches)) {
            return null;
        }

        return [$matches[1], $matches[2]];
    }
}
