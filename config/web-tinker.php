<?php

return [


    'enabled' => env('APP_DEBUG') && env('APP_ENV') === 'local',

    /*
     * The web tinker page will be available on this path.
     */
    'path' => '/tinker',

    'dark_theme' => false,
];