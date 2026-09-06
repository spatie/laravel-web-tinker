<?php

namespace Spatie\WebTinker\Completion\StringArgument;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

/**
 * Completes dotted configuration keys inside `config("…")`.
 *
 * Both leaves and the groups above them are offered, since `config("mail")`
 * returning the whole group is as valid a thing to want as a single value. As
 * with {@see EnvKeysSource}, only key names are exposed.
 */
class ConfigKeysSource extends StringArgumentSource
{
    public function meta(): string
    {
        return 'config';
    }

    protected function functions(): array
    {
        return ['config'];
    }

    protected function keyPattern(): string
    {
        return '[A-Za-z0-9_.\-]';
    }

    protected function candidates(): array
    {
        $keys = [];

        foreach (array_keys(Arr::dot(Config::all())) as $leaf) {
            $keys[$leaf] = true;

            $segments = explode('.', $leaf);

            array_pop($segments);

            while ($segments !== []) {
                $keys[implode('.', $segments)] = true;

                array_pop($segments);
            }
        }

        return array_keys($keys);
    }
}
