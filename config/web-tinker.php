<?php

return [

    /*
     * The web tinker page will be available on this path.
     */
    'path' => '/tinker',

    /*
     * Possible values are 'auto', 'light' and 'dark'.
     */
    'theme' => 'auto',

    /*
     * By default this package will only run in local development.
     * Do not change this, unless you know what you are doing.
     */
    'enabled' => env('APP_ENV') === 'local',

    /*
    * This class can modify the output returned by Tinker. You can replace this with
    * any class that implements \Spatie\WebTinker\OutputModifiers\OutputModifier.
    */
    'output_modifier' => \Spatie\WebTinker\OutputModifiers\PrefixDateTime::class,

    /*
    * These middleware will be assigned to every WebTinker route, giving you the chance
    * to add your own middlewares to this list or change any of the existing middleware.
    */
    'middleware' => [
        Illuminate\Cookie\Middleware\EncryptCookies::class,
        Illuminate\Session\Middleware\StartSession::class,
        Spatie\WebTinker\Http\Middleware\Authorize::class,
    ],

    /*
     * Editor autocompletion: class names from Composer's classmap and your own
     * PSR-4 roots, the members of a variable whose type can be read from the
     * code, environment variable names inside env(), configuration keys inside
     * config(), plus PsySH's own keyword, function, constant and static member
     * matchers.
     *
     * Only names are ever sent to the browser — never the value behind an env
     * or config key. Building the class index walks your source tree, so it is
     * cached for `cache_ttl` seconds (0 disables caching).
     */
    'completion' => [
        'enabled' => env('WEB_TINKER_COMPLETION_ENABLED', true),
        'limit' => 100,
        'cache_ttl' => 300,
    ],

    /*
     * If you want to fine-tune PsySH configuration specify
     * configuration file name, relative to the root of your
     * application directory.
     */
    'config_file' => env('PSYSH_CONFIG', null),
];
