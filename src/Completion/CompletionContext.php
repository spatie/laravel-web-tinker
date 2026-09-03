<?php

namespace Spatie\WebTinker\Completion;

/**
 * The state a matcher needs to decide what to complete: the full snippet, the
 * caret offset, and a few lazily derived views on the code left of the caret.
 */
class CompletionContext
{
    /** @var array|null Cached PsySH-style token list for the code left of the caret. */
    protected ?array $tokens = null;

    public function __construct(
        public readonly string $code,
        public readonly int $cursor
    ) {
    }

    public static function make(string $code, int $cursor): self
    {
        return new self($code, max(0, min($cursor, mb_strlen($code, '8bit'))));
    }

    /** Everything left of the caret. Completion never looks at what follows it. */
    public function upToCursor(): string
    {
        return substr($this->code, 0, $this->cursor);
    }

    /**
     * PsySH's matchers expect the whitespace-free token list of the current
     * line, so mirror what its AutoCompleter builds before dispatching.
     *
     * @see \Psy\TabCompletion\AutoCompleter::processCallback()
     */
    public function tokens(): array
    {
        if ($this->tokens !== null) {
            return $this->tokens;
        }

        $tokens = $this->tokenize('<?php '.$this->upToCursor());

        $tokens = array_filter($tokens, function ($token) {
            return ! (is_array($token) && $token[0] === T_WHITESPACE);
        });

        return $this->tokens = array_values($tokens);
    }

    /** The `readline_info()` shape PsySH matchers read `line_buffer` and `end` from. */
    public function info(): array
    {
        $line = $this->upToCursor();

        return [
            'line_buffer' => $line,
            'point' => strlen($line),
            'end' => strlen($line),
        ];
    }

    /**
     * Offset where the identifier under the caret starts — the replacement
     * anchor for class, function, keyword and constant completions. Namespace
     * separators are part of the identifier so `App\Mod` replaces as a whole.
     */
    public function identifierStart(): int
    {
        $line = $this->upToCursor();

        if (preg_match('/[A-Za-z0-9_\x80-\xff\\\\]+$/', $line, $matches)) {
            return $this->cursor - strlen($matches[0]);
        }

        return $this->cursor;
    }

    /**
     * Is the caret reaching into something — after `::` or `->`?
     *
     * PsySH's function and keyword matchers answer `PMLog::deb` with
     * `debug_backtrace()`, because readline would never have offered them
     * there in the first place. Knowing which side of an arrow the caret sits
     * on is what keeps the two groups apart.
     */
    public function isMemberAccess(): bool
    {
        return (bool) preg_match('/(?:::|->)\\s*\\w*$/', $this->upToCursor());
    }

    /**
     * Is there anything at the caret for a suggestion to attach to?
     *
     * Without this, a caret sitting on whitespace matches the keyword and
     * function matchers on an empty prefix and the editor is handed every
     * identifier PHP knows about.
     */
    public function anchorsCompletion(): bool
    {
        return $this->identifierPrefix() !== ''
            || (bool) preg_match('/(?:::|->|\\\\)$/', $this->upToCursor());
    }

    public function identifierPrefix(): string
    {
        return substr($this->code, $this->identifierStart(), $this->cursor - $this->identifierStart());
    }

    /**
     * Tokenizing half-typed code is expected to be noisy — an unterminated
     * string or comment makes PHP emit a warning that says nothing useful to
     * someone mid-keystroke, so swallow diagnostics for this call only.
     */
    protected function tokenize(string $code): array
    {
        set_error_handler(function () {
            return true;
        });

        try {
            return token_get_all($code);
        } finally {
            restore_error_handler();
        }
    }
}
