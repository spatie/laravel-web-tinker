<?php

namespace Spatie\WebTinker\Completion\Source;

use Psy\Completion\AnalysisResult;
use Psy\Completion\CompletionKind;
use Psy\Completion\Source\SourceInterface;
use Spatie\WebTinker\Completion\ClassIndex;

/**
 * Class names, from everywhere the application can autoload rather than from
 * what it happens to have loaded.
 *
 * PsySH's own catalog is built from `get_declared_classes()`, which is the
 * right answer in a long-running shell where the user has been pulling classes
 * in as they go. In a web request it is whatever the framework autoloaded on
 * the way to rendering the page — a few hundred classes, almost none of them
 * the user's own. {@see ClassIndex} reads Composer's classmap and the
 * application's own source instead.
 *
 * Registered alongside PsySH's catalog rather than in place of it: the engine
 * merges and de-duplicates, so anything already loaded is still offered.
 */
class ClassIndexSource implements SourceInterface
{
    /** Enough prefix matches to be worth narrowing to, per the interface's guidance. */
    protected const PREFIX_MATCH_THRESHOLD = 10;

    public function __construct(
        protected ClassIndex $index,
        protected int $limit = 100
    ) {
    }

    public function appliesToKind(int $kinds): bool
    {
        $classLike = CompletionKind::CLASS_NAME
            | CompletionKind::INTERFACE_NAME
            | CompletionKind::TRAIT_NAME;

        return ($kinds & $classLike) !== 0;
    }

    public function getCompletions(AnalysisResult $analysis): array
    {
        $prefix = ltrim($analysis->prefix, '\\');

        if ($prefix === '') {
            return [];
        }

        $matches = $this->matching($prefix);

        // Below the threshold the engine's fuzzy matcher should see the whole
        // index; above it, an index this size would swamp everything else.
        return count($matches) >= self::PREFIX_MATCH_THRESHOLD
            ? array_slice($matches, 0, $this->limit)
            : $matches;
    }

    /**
     * Classes whose short or fully qualified name starts with the prefix,
     * exact hits first: what was typed in full is what was meant, so `Sale`
     * offers `Sale` before `SaleFailed`.
     *
     * @return string[]
     */
    protected function matching(string $prefix): array
    {
        $prefix = mb_strtolower($prefix);

        $exact = [];
        $short = [];
        $qualified = [];

        foreach ($this->index->all() as $class) {
            $lowerClass = mb_strtolower($class);
            $lowerShort = mb_strtolower($this->shortNameOf($class));

            if ($lowerClass === $prefix || $lowerShort === $prefix) {
                $exact[] = $class;
            } elseif (str_starts_with($lowerShort, $prefix)) {
                $short[] = $class;
            } elseif (str_starts_with($lowerClass, $prefix)) {
                $qualified[] = $class;
            }
        }

        // Among short-name matches the closest fit first, so `Compan` offers
        // `Company` ahead of `CompaniesAddAccountTypeColumn`. The engine's
        // fuzzy matcher scores these alike and keeps the order it is given.
        usort($short, function (string $a, string $b) {
            $byLength = strlen($this->shortNameOf($a)) <=> strlen($this->shortNameOf($b));

            return $byLength !== 0 ? $byLength : strcmp($a, $b);
        });

        return array_merge($exact, $short, $qualified);
    }

    /** The name without its namespace — the class itself when it has none. */
    protected function shortNameOf(string $class): string
    {
        $separator = strrpos($class, '\\');

        return $separator === false ? $class : substr($class, $separator + 1);
    }
}
