<?php

namespace Spatie\WebTinker\Completion;

use Psy\Completion\CompletionEngine;
use Psy\Completion\CompletionRequest;
use Spatie\WebTinker\Completion\Source\AnalysisCapture;
use Spatie\WebTinker\Completion\StringArgument\StringArgumentSource;

/**
 * Turns a snippet and a caret offset into suggestions the editor can apply.
 *
 * The work is PsySH's: {@see CompletionEngine} parses the buffer, decides what
 * kind of thing belongs at the caret, resolves the type of whatever sits left
 * of it and collects candidates. This adds the three things a browser needs
 * that a readline prompt does not.
 *
 * Where the replacement starts, because the editor has to replace text rather
 * than append to a prompt. What kind of thing each suggestion is, so the list
 * can say so. And the framework's own string-literal conventions, which are
 * not PHP expressions and so cannot be part of the engine's grammar.
 */
class Completer
{
    /** @param StringArgumentSource[] $stringArgumentSources */
    public function __construct(
        protected CompletionEngine $engine,
        protected CompletionLabeler $labeler,
        protected AnalysisCapture $capture,
        protected array $stringArgumentSources = [],
        protected int $limit = 100
    ) {
    }

    public function complete(string $code, int $cursor): CompletionResult
    {
        $cursor = max(0, min($cursor, mb_strlen($code)));

        if ($result = $this->completeStringArgument($code, $cursor)) {
            return $result;
        }

        $completions = $this->engine->getCompletions(
            new CompletionRequest($code, $cursor, CompletionRequest::MODE_SUGGESTION)
        );

        $analysis = $this->capture->last();

        if ($analysis === null) {
            return CompletionResult::empty($cursor);
        }

        return new CompletionResult(
            $cursor - mb_strlen($analysis->prefix),
            array_slice($this->labeler->describe($completions, $analysis), 0, $this->limit)
        );
    }

    /**
     * `env("APP_…` and `config("database.…` are answered on their own, before
     * the engine sees the buffer.
     *
     * @see StringArgumentSource for why they sit outside the pipeline
     */
    protected function completeStringArgument(string $code, int $cursor): ?CompletionResult
    {
        foreach ($this->stringArgumentSources as $source) {
            $partial = $source->partialKey($code, $cursor);

            if ($partial === null) {
                continue;
            }

            $completions = array_map(
                fn (string $key) => new Completion($key, $source->meta()),
                $source->complete($partial)
            );

            return new CompletionResult(
                $cursor - mb_strlen($partial),
                array_slice($completions, 0, $this->limit)
            );
        }

        return null;
    }
}
