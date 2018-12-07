const mix = require('laravel-mix');
var tailwindcss = require('tailwindcss');
require('laravel-mix-purgecss');

mix
    .setPublicPath('public')
    .postCss('resources/css/app.css', 'public', [tailwindcss('./tailwind.js')])
    .postCss('resources/css/app-dark.css', 'public', [tailwindcss('./tailwind.js')])
    .purgeCss()
    .js('resources/js/app.js', 'public')
    .version()
    .copy('public', '../web-tinker-app/public/vendor/web-tinker');
