<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Orchestra\Testbench\Foundation\Application;
use Spatie\WebTinker\WebTinkerServiceProvider;

require __DIR__.'/../../vendor/autoload.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (in_array($path, ['/vendor/web-tinker/app.js', '/vendor/web-tinker/app.css'])) {
    header('Content-Type: '.(str_ends_with($path, '.js') ? 'application/javascript' : 'text/css'));
    readfile(__DIR__.'/../../public/'.basename($path));

    return;
}

$app = Application::create(options: ['extra' => ['providers' => [WebTinkerServiceProvider::class]]]);
$app->detectEnvironment(fn () => 'local');
$app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
$app['config']->set('web-tinker.enabled', true);
$app['config']->set('web-tinker.dump.enabled', ! isset($_GET['plain']));
$app['config']->set('web-tinker.theme', $_GET['theme'] ?? 'light');

$assetPath = $app->publicPath('vendor/web-tinker');

if (! is_dir($assetPath)) {
    mkdir(dirname($assetPath), 0755, true);
    symlink(realpath(__DIR__.'/../../public'), $assetPath);
}

$kernel = $app->make(Kernel::class);
$request = Request::capture();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
