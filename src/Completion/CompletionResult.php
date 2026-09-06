<?php

namespace Spatie\WebTinker\Completion;

/**
 * What the editor needs to apply a suggestion: the offset the replacement
 * starts at, and the suggestions themselves.
 *
 * The offset is computed server side because the rules for where a token
 * begins already live here — duplicating them in JavaScript is how the two
 * halves drift apart.
 */
class CompletionResult
{
    /** @param Completion[] $completions */
    public function __construct(
        public readonly int $from,
        public readonly array $completions
    ) {
    }

    public static function empty(int $from = 0): self
    {
        return new self($from, []);
    }

    public function toArray(): array
    {
        return [
            'from' => $this->from,
            'completions' => array_map(fn (Completion $completion) => $completion->toArray(), $this->completions),
        ];
    }
}
