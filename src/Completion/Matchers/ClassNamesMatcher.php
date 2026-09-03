<?php

namespace Spatie\WebTinker\Completion\Matchers;

use Spatie\WebTinker\Completion\ClassIndex;
use Spatie\WebTinker\Completion\Completion;
use Spatie\WebTinker\Completion\CompletionContext;

/**
 * Completes class, interface, trait and enum names from {@see ClassIndex}.
 *
 * Matching is done on the short name as well as the fully qualified one, so
 * typing `Sale` offers `App\Models\Sale` without anyone having to remember
 * which namespace it lives in. Short-name hits sort first — that is almost
 * always what was meant.
 */
class ClassNamesMatcher extends BaseMatcher
{
    public function __construct(
        protected ClassIndex $index,
        protected int $limit = 50
    ) {
    }

    /** The name without its namespace — the class itself when it has none. */
    protected function shortNameOf(string $class): string
    {
        $separator = strrpos($class, '\\');

        return $separator === false ? $class : substr($class, $separator + 1);
    }

    public function matches(CompletionContext $context): bool
    {
        if ($context->identifierPrefix() === '') {
            return false;
        }

        // `$foo`, `Foo::bar` and `$foo->bar` belong to the variable, static and
        // object matchers; only a bare identifier is a class name here.
        return ! preg_match('/(?:\$|::|->)[A-Za-z0-9_\x80-\xff]*$/', $context->upToCursor());
    }

    public function complete(CompletionContext $context): array
    {
        $prefix = mb_strtolower(ltrim($context->identifierPrefix(), '\\'));

        $exact = [];
        $short = [];
        $qualified = [];

        foreach ($this->index->all() as $class) {
            $lowerClass = mb_strtolower($class);
            $lowerShortName = mb_strtolower($this->shortNameOf($class));

            if ($lowerClass === $prefix || $lowerShortName === $prefix) {
                $exact[] = $class;

                continue;
            }

            if (str_starts_with($lowerShortName, $prefix)) {
                $short[] = $class;

                continue;
            }

            if (str_starts_with($lowerClass, $prefix)) {
                $qualified[] = $class;
            }
        }

        // What was typed in full is what was meant: `Sale` before `SaleFailed`,
        // and both before `App\Something\ThatEndsWithSale`.
        $matches = array_slice(array_merge($exact, $short, $qualified), 0, $this->limit);

        return array_map(
            fn (string $class) => new Completion($class, 'class'),
            $matches
        );
    }
}
