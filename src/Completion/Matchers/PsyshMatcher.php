<?php

namespace Spatie\WebTinker\Completion\Matchers;

use Psy\TabCompletion\Matcher\AbstractMatcher as PsyshAbstractMatcher;
use Spatie\WebTinker\Completion\Completion;
use Spatie\WebTinker\Completion\CompletionContext;

/**
 * Adapts one of PsySH's own tab-completion matchers to this package's
 * interface, so keywords, functions, constants and static members come from
 * the same implementation the CLI shell uses.
 *
 * @see \Psy\TabCompletion\AutoCompleter
 */
class PsyshMatcher extends BaseMatcher
{
    public function __construct(
        protected PsyshAbstractMatcher $matcher,
        protected string $meta,
        /** True for matchers that complete what follows `::` or `->`. */
        protected bool $completesMembers = false
    ) {
    }

    public function matches(CompletionContext $context): bool
    {
        if ($context->isMemberAccess() !== $this->completesMembers) {
            return false;
        }

        return $this->matcher->hasMatched($context->tokens());
    }

    public function complete(CompletionContext $context): array
    {
        $matches = $this->matcher->getMatches($context->tokens(), $context->info());

        $matches = array_filter($matches, fn ($match) => is_string($match) && $match !== '');

        $matches = array_map([$this, 'memberName'], $matches);

        return array_map(
            fn (string $match) => new Completion($match, $this->meta),
            array_values(array_unique($matches))
        );
    }

    /**
     * PsySH returns static members fully qualified — `Foo::bar` — because
     * readline replaces the whole word it completed. Here the replacement
     * starts at the member, so keeping the qualifier would insert the class
     * name a second time.
     */
    protected function memberName(string $match): string
    {
        $separator = strrpos($match, '::');

        return $separator === false ? $match : substr($match, $separator + 2);
    }
}
