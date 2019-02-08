<?php

return [

    /*
     * The web tinker page will be available on this path.
     */
    'path' => '/tinker',

    /*
     * If light hurts you eyes, set this to true.
     */
    'dark_theme' => false,

    /*
     * By default this package will only run in local development.
     * Do not change this, unless you know what your are doing.
     */
    'enabled' => env('APP_ENV') === 'local',
];
