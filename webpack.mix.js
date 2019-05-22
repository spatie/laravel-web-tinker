const mix = require('laravel-mix');
require('laravel-mix-purgecss');

mix
    .setPublicPath('public')
    .postCss('resources/css/app.css', 'public')
    .purgeCss({
        whitelistPatterns: [/CodeMirror/, /cm/, /^theme-/],
    })
    .js('resources/js/app.js', 'public')
    .version()
    .options({
        // Our PostCSS plugins are defined in a standard `postcss.config.js`
        // file, which we'll read for plugins.
        postCss: require('./postcss.config').plugins,
    })
    .copy('public', '../web-tinker-app/public/vendor/web-tinker');
