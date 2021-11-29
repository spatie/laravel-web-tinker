<?php

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Spatie\WebTinker\Http\Middleware\Authorize;

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
        EncryptCookies::class,
        StartSession::class,
        Authorize::class,
    ],

    /*
     * If you want to fine-tune PsySH configuration specify
     * configuration file name, relative to the root of your
     * application directory.
     */
    'config_file' => env('PSYSH_CONFIG', null),
];
