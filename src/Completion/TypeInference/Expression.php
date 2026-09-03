<?php

namespace Spatie\WebTinker\Completion\TypeInference;

/**
 * The slice of PHP expression syntax completion understands, as regex
 * fragments shared by the matcher (which decides whether the caret is in an
 * expression) and the resolver (which works out its type).
 *
 * Deliberately small. Arguments are matched as "no parentheses", so a nested
 * call inside the arguments — `User::find(intval($id))->…` — is not
 * recognised; completion goes quiet rather than resolving something wrong.
 */
final class Expression
{
    /** `$user`, `new User(…)`, `User::first(…)`, `app(User::class)`. */
    public const HEAD = '(?:\$\w+'
        .'|new\s+\\\\?[A-Za-z_][\w\\\\]*(?:\s*\([^()]*\))?'
        .'|(?:app|resolve)\s*\(\s*\\\\?[A-Za-z_][\w\\\\]*::class\s*\)'
        .'|\\\\?[A-Za-z_][\w\\\\]*::\w+\s*\([^()]*\))';

    /** One `->member` step, with or without a call. */
    public const LINK = '\s*->\s*\w+\s*(?:\([^()]*\))?';

    /** A head followed by any number of links: what a caret can sit behind. */
    public const RECEIVER = self::HEAD.'(?:'.self::LINK.')*';
}
