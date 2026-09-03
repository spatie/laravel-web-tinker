<?php

namespace Spatie\WebTinker\Completion\Matchers;

/**
 * Completes environment variable names inside `env("…")`.
 *
 * Names only — a value never leaves the server through this endpoint. Names
 * come from the process environment plus any `.env` files present, so keys
 * that are defined but currently unset (the common case for `.env.example`)
 * are still offered.
 */
class EnvKeysMatcher extends StringArgumentMatcher
{
    /** Files scanned for key names, relative to the application root. */
    protected const ENV_FILES = ['.env', '.env.example'];

    public function __construct(protected string $basePath)
    {
    }

    protected function functions(): array
    {
        return ['env'];
    }

    protected function meta(): string
    {
        return 'env';
    }

    protected function candidates(): array
    {
        $keys = array_keys($_ENV);

        foreach (static::ENV_FILES as $file) {
            $keys = array_merge($keys, $this->keysDeclaredIn($this->basePath.'/'.$file));
        }

        return array_values(array_unique($keys));
    }

    /**
     * Key names declared in a dotenv file. Only the part left of the first `=`
     * is ever read, so values are neither parsed nor retained.
     *
     * @return string[]
     */
    protected function keysDeclaredIn(string $path): array
    {
        if (! is_readable($path)) {
            return [];
        }

        $contents = @file_get_contents($path);

        if ($contents === false) {
            return [];
        }

        preg_match_all('/^\s*(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=/m', $contents, $matches);

        return $matches[1];
    }
}
