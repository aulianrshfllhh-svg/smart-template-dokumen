<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'logout',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Tangani CSRF TokenMismatchException (Error 419 Page Expired) secara otomatis di seluruh rute web
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($e instanceof TokenMismatchException || ($e instanceof HttpException && $e->getStatusCode() === 419)) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Sesi Anda telah kadaluarsa.'], 419);
                }
                return redirect()->route('login')
                    ->with('error', 'Sesi login Anda telah kadaluarsa (Expired). Silakan masuk kembali.');
            }
        });
    })->create();
