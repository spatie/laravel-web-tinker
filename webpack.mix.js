const mix = require('laravel-mix');
require('laravel-mix-purgecss');

mix
    .setPublicPath('public')
    .postCss('resources/css/app.css', 'public')
    .purgeCss({
        // `wt-` and `sf-dump-` classes only appear in markup built at runtime,
        // so PurgeCSS cannot see them in the source.
        whitelistPatterns: [/CodeMirror/, /cm/, /^theme-/, /^wt-/, /^sf-dump/],
    })
    .js('resources/js/app.js', 'public')
    .version()
    .options({
        // Our PostCSS plugins are defined in a standard `postcss.config.js`
        // file, which we'll read for plugins.
        postCss: require('./postcss.config').plugins,
    })
    .copy('public', '../web-tinker-app/public/vendor/web-tinker');
