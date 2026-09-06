<?php

namespace Spatie\WebTinker\Completion\StringArgument;

/**
 * Completes the string literal being typed as the first argument of a known
 * helper — `env("APP_…`, `config("database.…`.
 *
 * These sit outside PsySH's pipeline on purpose. An unterminated string is a
 * syntax error, so the parser never reaches the key: asked about `env("APP_`
 * the analyzer reports a failed parse and, once its refiners have guessed,
 * offers class and function names. Worse, the prefix it recovers stops at a
 * dot, so `config("database.def` would replace only `def`.
 *
 * What these contexts have in common is that they are conventions of a
 * framework rather than constructs of the language, so they are recognised
 * from the raw text and answered on their own — no PHP grammar involved, and
 * nothing else offered alongside.
 */
abstract class StringArgumentSource
{
    /** Helper names whose first string argument this source completes. */
    abstract protected function functions(): array;

    /** @return string[] every key that could be offered, unfiltered */
    abstract protected function candidates(): array;

    abstract public function meta(): string;

    /** Character class of a partially typed key, as a regex fragment. */
    protected function keyPattern(): string
    {
        return '[A-Za-z0-9_]';
    }

    /**
     * The key typed so far, or null when the caret is not inside such a
     * literal.
     */
    public function partialKey(string $code, int $cursor): ?string
    {
        $functions = implode('|', array_map('preg_quote', $this->functions()));

        $pattern = '/(?:^|[^\w\\\\])(?:'.$functions.')\s*\(\s*[\'"]('.$this->keyPattern().'*)$/i';

        if (! preg_match($pattern, substr($code, 0, $cursor), $matches)) {
            return null;
        }

        return $matches[1];
    }

    /** @return string[] */
    public function complete(string $partial): array
    {
        $keys = $this->candidates();

        if ($partial !== '') {
            $partial = mb_strtolower($partial);

            $keys = array_filter(
                $keys,
                fn (string $key) => str_starts_with(mb_strtolower($key), $partial)
            );
        }

        $keys = array_values(array_unique($keys));

        sort($keys);

        return $keys;
    }
}
