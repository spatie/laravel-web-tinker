<?php

namespace Spatie\WebTinker\Output;

enum OutputFormat: string
{
    /** PsySH's own plain-text rendering. The default, and what API clients get. */
    case Text = 'text';

    /** Symfony VarDumper markup: typed, collapsible, navigable. */
    case Html = 'html';

    public static function fromRequestValue(?string $value, self $default = self::Text): self
    {
        return self::tryFrom((string) $value) ?? $default;
    }
}
