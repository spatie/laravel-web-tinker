<?php

namespace Spatie\WebTinker\Completion\Matchers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

/**
 * Completes dotted configuration keys inside `config("…")`.
 *
 * Both leaves and the groups above them are offered, so `config("database.` is
 * as useful a stopping point as the full key. As with {@see EnvKeysMatcher},
 * only key names are exposed.
 */
class ConfigKeysMatcher extends StringArgumentMatcher
{
    protected function functions(): array
    {
        return ['config'];
    }

    protected function meta(): string
    {
        return 'config';
    }

    protected function keyPattern(): string
    {
        return '[A-Za-z0-9_.\-]';
    }

    protected function candidates(): array
    {
        $leaves = array_keys(Arr::dot(Config::all()));

        $keys = [];

        foreach ($leaves as $leaf) {
            $keys[$leaf] = true;

            // Every ancestor is a valid key too: `config("mail.mailers")`
            // returns the whole group.
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
