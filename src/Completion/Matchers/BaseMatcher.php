<?php

namespace Spatie\WebTinker\Completion\Matchers;

use Spatie\WebTinker\Completion\CompletionContext;

abstract class BaseMatcher implements Matcher
{
    public function replaceFrom(CompletionContext $context): int
    {
        return $context->identifierStart();
    }

    public function isExclusive(): bool
    {
        return false;
    }

    /** @return string[] */
    protected function filterByPrefix(array $candidates, string $prefix): array
    {
        if ($prefix === '') {
            return $candidates;
        }

        $prefix = mb_strtolower($prefix);

        return array_values(array_filter($candidates, function (string $candidate) use ($prefix) {
            return str_starts_with(mb_strtolower($candidate), $prefix);
        }));
    }
}
