<?php

namespace Spatie\WebTinker\Completion\Matchers;

use Spatie\WebTinker\Completion\CompletionContext;

interface Matcher
{
    /** Does the code left of the caret put us in this matcher's territory? */
    public function matches(CompletionContext $context): bool;

    /**
     * Suggestions for the current caret position.
     *
     * @return \Spatie\WebTinker\Completion\Completion[]
     */
    public function complete(CompletionContext $context): array;

    /** Offset where the text this matcher replaces begins. */
    public function replaceFrom(CompletionContext $context): int;

    /**
     * An exclusive matcher suppresses every non-exclusive one. String-argument
     * matchers set this: halfway through `env("APP_` the token-based matchers
     * see a broken string and offer noise, so the env keys stand alone.
     */
    public function isExclusive(): bool;
}
