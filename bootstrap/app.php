<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

require_once __DIR__ . '/../app/Providers/AppServiceProvider.php';

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: dirname(__DIR__).'/routes/web.php',
        api: dirname(__DIR__).'/routes/api.php',
        commands: dirname(__DIR__).'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append([
            \App\Http\Middleware\CheckActivation::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->useAppPath($app->basePath('app'));
$app->usePublicPath($app->basePath('public'));
$app->useStoragePath($app->basePath('storage'));
$app->useDatabasePath($app->basePath('database'));
$app->instance('path.resources', $app->basePath('resources'));

return $app;
