<?php

namespace Spatie\WebTinker\Completion\Matchers;

use Spatie\WebTinker\Completion\Completion;
use Spatie\WebTinker\Completion\CompletionContext;

/**
 * Base for matchers that complete the string literal being typed as the first
 * argument of a known helper — `env("APP_…`, `config("database.…`.
 *
 * These deliberately work on raw text rather than tokens: an unterminated
 * string is a parse error, so by the time the key is worth completing the
 * tokenizer has nothing useful left to say about it. For the same reason they
 * are exclusive — every token-based matcher is guessing at that point.
 */
abstract class StringArgumentMatcher extends BaseMatcher
{
    /** Helper names whose first string argument this matcher completes. */
    abstract protected function functions(): array;

    /** @return string[] Every key that could be offered, unfiltered. */
    abstract protected function candidates(): array;

    abstract protected function meta(): string;

    /** Character class of a partially typed key, as a regex fragment. */
    protected function keyPattern(): string
    {
        return '[A-Za-z0-9_]';
    }

    public function matches(CompletionContext $context): bool
    {
        return $this->partialKey($context) !== null;
    }

    public function complete(CompletionContext $context): array
    {
        $partial = $this->partialKey($context) ?? '';

        $keys = $this->filterByPrefix($this->candidates(), $partial);

        sort($keys);

        return array_map(
            fn (string $key) => new Completion($key, $this->meta()),
            $keys
        );
    }

    public function replaceFrom(CompletionContext $context): int
    {
        return $context->cursor - strlen($this->partialKey($context) ?? '');
    }

    public function isExclusive(): bool
    {
        return true;
    }

    /** The key typed so far, or null when the caret is not in such a literal. */
    protected function partialKey(CompletionContext $context): ?string
    {
        $functions = implode('|', array_map('preg_quote', $this->functions()));

        $pattern = '/(?:^|[^\w\\\\])(?:'.$functions.')\s*\(\s*[\'"]('.$this->keyPattern().'*)$/i';

        if (! preg_match($pattern, $context->upToCursor(), $matches)) {
            return null;
        }

        return $matches[1];
    }
}
