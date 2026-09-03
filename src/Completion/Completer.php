<?php

namespace Spatie\WebTinker\Completion;

use Spatie\WebTinker\Completion\Matchers\Matcher;

/**
 * Turns a snippet and a caret offset into suggestions, by asking each
 * registered matcher whether it recognises the context.
 *
 * Nothing here executes the user's code: completion is pure inspection of the
 * text, the class index, and the loaded configuration.
 */
class Completer
{
    /** @param Matcher[] $matchers */
    public function __construct(
        protected array $matchers,
        protected int $limit = 100
    ) {
    }

    public function complete(string $code, int $cursor): CompletionResult
    {
        $context = CompletionContext::make($code, $cursor);

        $matchers = array_values(array_filter(
            $this->matchers,
            fn (Matcher $matcher) => $matcher->matches($context)
        ));

        if ($matchers === []) {
            return CompletionResult::empty($context->identifierStart());
        }

        $exclusive = array_values(array_filter($matchers, fn (Matcher $matcher) => $matcher->isExclusive()));

        if ($exclusive !== []) {
            $matchers = $exclusive;
        } elseif (! $context->anchorsCompletion()) {
            return CompletionResult::empty($context->identifierStart());
        }

        $completions = [];

        foreach ($matchers as $matcher) {
            foreach ($matcher->complete($context) as $completion) {
                // First matcher to claim a value wins its meta label: matchers
                // are registered most-specific first.
                $completions[$completion->value] ??= $completion;
            }
        }

        return new CompletionResult(
            $matchers[0]->replaceFrom($context),
            array_slice(array_values($completions), 0, $this->limit)
        );
    }
}
