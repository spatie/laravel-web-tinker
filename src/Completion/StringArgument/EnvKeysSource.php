<?php

namespace Spatie\WebTinker\Completion\StringArgument;

/**
 * Completes environment variable names inside `env("…")`.
 *
 * Names only — a value never leaves the server through this endpoint. Names
 * come from the process environment plus any dotenv files present, so keys
 * that are declared but currently unset are still offered.
 */
class EnvKeysSource extends StringArgumentSource
{
    /** Files scanned for key names, relative to the application root. */
    protected const ENV_FILES = ['.env', '.env.example'];

    public function __construct(protected string $basePath)
    {
    }

    public function meta(): string
    {
        return 'env';
    }

    protected function functions(): array
    {
        return ['env'];
    }

    protected function candidates(): array
    {
        $keys = array_keys($_ENV);

        foreach (self::ENV_FILES as $file) {
            $keys = array_merge($keys, $this->keysDeclaredIn($this->basePath.'/'.$file));
        }

        return $keys;
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
