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
     * How results are rendered by default. 'html' returns Symfony VarDumper
     * markup — typed and collapsible; 'text' returns PsySH's plain output.
     *
     * The browser UI always asks for 'html' explicitly, so leaving this on
     * 'text' keeps any script or test suite posting to this endpoint on the
     * output format it was written against.
     */
    'output_format' => 'text',

    /*
     * Rendering results as VarDumper markup. Switch it off to fall back to
     * PsySH's plain output everywhere, whatever a request asks for.
     *
     * The limits guard the browser against a dump of something enormous, not
     * the server.
     */
    'dump' => [
        'enabled' => env('WEB_TINKER_RICH_OUTPUT_ENABLED', true),
        'max_depth' => 6,
        'max_items' => 250,
        'max_string_length' => 2500,
    ],

    /*
     * If you want to fine-tune PsySH configuration specify
     * configuration file name, relative to the root of your
     * application directory.
     */
    'config_file' => env('PSYSH_CONFIG', null),
];
