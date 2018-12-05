const mix = require('laravel-mix');
const webpack = require('webpack');

mix
    .options({
        uglify: {
            uglifyOptions: {
                compress: {
                    drop_console: true,
                }
            }
        }
    })
    .setPublicPath('public')
    .js('resources/js/app.js', 'public')
    .postCss('resources/css/app.css', 'public')
    .postCss('resources/css/app-dark.css', 'public')
    .version()
    .copy('public', '../web-tinker-app/public/vendor/web-tinker')
    .webpackConfig({
        resolve: {
            symlinks: false,
            alias: {
                '@': path.resolve(__dirname, 'resources/js/'),
            }
        },
        plugins: [
            new webpack.IgnorePlugin(/^\.\/locale$/, /moment$/)
        ],
    });
