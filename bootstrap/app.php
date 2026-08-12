<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (QueryException $exception, Request $request) {
            $isSqliteBusy = $exception->connectionName === 'sqlite'
                && (str_contains(strtolower($exception->getMessage()), 'database is locked')
                    || str_contains(strtolower($exception->getMessage()), 'database is busy'));

            if (! $isSqliteBusy) {
                return null;
            }

            $response = $request->expectsJson()
                ? response()->json([
                    'message' => 'Database đang bận. Vui lòng chờ thao tác khác hoàn tất rồi thử lại.',
                ], 503)
                : response()->view('errors.database-busy', status: 503);

            return $response->header('Retry-After', '2');
        });
    })->create();
