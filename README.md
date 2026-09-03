# Artisan Tinker in your browser

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-web-tinker.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-web-tinker)
![GitHub Workflow Status](https://github.com/spatie/laravel-web-tinker/actions/workflows/run-tests.yml/badge.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-web-tinker.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-web-tinker)

Artisan's tinker command is a great way to tinker with your application in the terminal. Unfortunately running a few lines of code, making edits, and copy/pasting code can be bothersome. Wouldn't it be great to tinker in the browser?

This package will add a route to your application where you can tinker to your heart's content.

![Web tinker light](https://spatie.github.io/laravel-web-tinker/light.png)

In case light hurts your eyes, there's a dark mode too.

![Web tinker dark](https://spatie.github.io/laravel-web-tinker/dark.png)

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-web-tinker.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-web-tinker)

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
    * These middleware will be assigned to every WebTinker route, giving you the chance
    * to add your own middlewares to this list or change any of the existing middleware.
    */
    'middleware' => [
        Illuminate\Cookie\Middleware\EncryptCookies::class,
        Illuminate\Session\Middleware\StartSession::class,
        Spatie\WebTinker\Http\Middleware\Authorize::class,
    ],

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

## Autocompletion

The editor completes as you type, and on <kbd>Ctrl</kbd>+<kbd>Space</kbd>. Every
suggestion is produced by inspecting your code — nothing in the snippet is
executed to work out what fits.

| What you type | What you get |
| --- | --- |
| `new Use` | classes, interfaces, traits and enums, matched on the short **or** fully qualified name |
| `$user->` | the members of `$user`, including the columns and relations a model documents with `@property` |
| `User::` | static methods, constants and static properties |
| `env("APP_` | environment variable **names** |
| `config("database.` | configuration keys, groups as well as leaves |
| `str_re` | functions, constants and keywords, via PsySH's own matchers |

Accepting a method inserts the call and leaves the caret between the
parentheses.

Only names ever reach the browser. The value behind an env or config key is
never sent.

### How a variable's type is found

There is no shell context to reflect on — each request builds a fresh shell —
so the type comes from reading the assignment rather than running it:

```php
$user = User::first();      // Eloquent's static forwards
$user = new User;           // constructors
$user = app(User::class);   // the container
$user = $repo->findUser();  // declared and @return-annotated return types
/** @var User $user */      // an annotation, when all else fails
$user->…
```

Chains are followed link by link, through properties as well as calls, and can
start from a static call with no variable in between:

```php
Company::getById(1)->individuals->first()->…
```

Every step has to say what it returns, natively or in a docblock. A static
method with neither — `public static function getById($id)` with no `@return` —
resolves to nothing, and the list stays empty rather than guessing. The one
exception is Eloquent's own static forwards (`first`, `find`, `create`, …),
which are `__callStatic` and so have nothing to reflect on at all.

### Eloquent models

A model's columns and relations exist only at runtime — `$company->individuals`
goes through `__get`, and reflection will never find it. What does describe
them is the annotation block above the class, so that is what gets read:

```php
/**
 * @property string                  $site_description
 * @property Individual[]|Collection $individuals
 *
 * @method static Builder active()
 */
class Company extends Model
```

`@property`, `@property-read`, `@property-write` and `@method` are all
understood, inherited annotations included, and documented members are offered
before the two hundred methods a model inherits.

Short names in an annotation are resolved against the `use` statements of the
file the annotation was written in, so `Collection` means the collection that
file imported. A union like `Individual[]|Collection` describes the value twice
— collection and element — and both are kept, which is what lets
`$company->individuals->first()->email` land on the individual. Generic
annotations (`Collection<int, Individual>`) work the same way.

### Where class names come from

PsySH completes against `get_declared_classes()`, which in a web request is
whatever the framework happened to autoload. This package builds a real index
instead: Composer's classmap, a scan of your own PSR-4 roots (reading the
`namespace` and `class` declared in each file, so global-namespace classes are
indexed correctly), and the registered facade aliases.

The scan is cached for `completion.cache_ttl` seconds and re-keyed on every
`composer dump-autoload`. Set the TTL to `0` while working on the index itself.

```php
// config/web-tinker.php
'completion' => [
    'enabled' => env('WEB_TINKER_COMPLETION_ENABLED', true),
    'limit' => 100,
    'cache_ttl' => 300,
],
```

Set `WEB_TINKER_COMPLETION_ENABLED=false` to turn the whole feature off: the
endpoint then returns nothing and the editor stops asking.

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

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security

If you've found a bug regarding security please mail [security@spatie.be](mailto:security@spatie.be) instead of using the issue tracker.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

This package was inspired by and uses code from the [nova-tinker-tool](https://github.com/beyondcode/nova-tinker-tool) package by [Marcel Pociot](https://github.com/mpociot).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
