<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Registrar middleware para prevenir envíos duplicados
        $middleware->alias([
            'prevent.duplicate' => \App\Http\Middleware\PreventDuplicateSubmissions::class,
        ]);

        // Aplicar globalmente a rutas web (opcional - comentar si prefieres aplicar solo en rutas específicas)
        $middleware->web(append: [
            \App\Http\Middleware\PreventDuplicateSubmissions::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
