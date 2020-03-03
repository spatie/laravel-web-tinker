# Artisan Tinker in your browser

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-web-tinker.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-web-tinker)
![GitHub Workflow Status](https://img.shields.io/github/workflow/status/spatie/laravel-web-tinker/run-tests?label=tests)
[![StyleCI](https://github.styleci.io/repos/160545642/shield?branch=master)](https://github.styleci.io/repos/160545642)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/laravel-web-tinker.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/laravel-web-tinker)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-web-tinker.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-web-tinker)

Artisan's tinker command is a great way to tinker with your application in the terminal. Unfortunately running a few lines of code, making edits, and copy/pasting code can be bothersome. Wouldn't it be great to tinker in the browser?

This package will add a route to your application where you can tinker to your heart's content.

![Web tinker light](https://spatie.github.io/laravel-web-tinker/light.png)

In case light hurts your eyes, there's a dark mode too.

![Web tinker dark](https://spatie.github.io/laravel-web-tinker/dark.png)

## Support us

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us). 

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).


## 🚨 A word to the wise 🚨

This package can run arbitrary code. Unless you know what you are doing, you should never install or use this in a production environment, or any environment where you handle real world data.

## Installation

You can install the package via composer:

```bash
composer require spatie/laravel-web-tinker --dev
```

Next, you must publish the assets from this package by running this command.

```bash
php artisan web-tinker:install
```

Optionally, you can publish the config file of the package.

```bash
php artisan vendor:publish --provider="Spatie\WebTinker\WebTinkerServiceProvider" --tag="config"
```

This is the content that will be published to `config/web-tinker.php`

```php
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
     * Do not change this, unless you know what your are doing.
     */
    'enabled' => env('APP_ENV') === 'local',

   /*
    * This class can modify the output returned by Tinker. You can replace this with
    * any class that implements \Spatie\WebTinker\OutputModifiers\OutputModifier.
    */
    'output_modifier' => \Spatie\WebTinker\OutputModifiers\PrefixDateTime::class,

    /*
     * If you want to fine-tune PsySH configuration specify
     * configuration file name, relative to the root of your
     * application directory.
     */
    'config_file' => env('PSYSH_CONFIG', null),
];
```

## Usage

By default this package will only run in a local environment.

Visit `/tinker` in your local environment of your app to view the tinker page.


## Authorization

Should you want to run this in another environment (we do not recommend this), there are two steps you must perform.

1. You must register a `viewWebTinker` ability. A good place to do this is in the `AuthServiceProvider` that ships with Laravel.

```php
public function boot()
{
    $this->registerPolicies();

    Gate::define('viewWebTinker', function ($user = null) {
        // return true if access to web tinker is allowed
    });
}
```

2. You must set the `enabled` variable in the `web-tinker` config file to `true`.

## Modifying the output

You can modify the output of tinker by specifying an output modifier in the `output_modifier` key of the `web-tinker` config file. An output modifier is any class that implements `\Spatie\WebTinker\OutputModifiers\OutputModifier`.

This is how that interface looks like.

```php
namespace Spatie\WebTinker\OutputModifiers;

interface OutputModifier
{
    public function modify(string $output = ''): string;
}
```

The default install of this package will use the `PrefixDataTime` output modifier which prefixes the output from Tinker with the current date time.

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

This package was inspired by and uses code from the [nova-tinker-tool](https://github.com/beyondcode/nova-tinker-tool) package by [Marcel Pociot](https://github.com/mpociot).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
