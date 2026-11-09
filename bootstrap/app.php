<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureSingleSession;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => EnsureAdmin::class,
        ]);

        // Har bir veb so'rovda bitta-qurilma qoidasi va xavfsizlik sarlavhalarini tekshiradi
        $middleware->appendToGroup('web', [
            EnsureSingleSession::class,
            SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (ThrottleRequestsException $e) {
            $request = request();
            Log::warning('Rate limit exceeded (429)', [
                'method' => $request->method(),
                'path' => $request->path(),
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
            ]);
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
