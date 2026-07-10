<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__.'/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register Sanctum middleware for API authentication
        $middleware->statefulApi();

        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'adminAuth' => \App\Http\Middleware\adminAuth::class,
            'supplierAuth' => \App\Http\Middleware\supplierAuth::class,
            'doctorAuth' => \App\Http\Middleware\doctorAuth::class,
            'adminSupplierAuth' => \App\Http\Middleware\adminSupplierAuth::class,
            'passwordAuth' => \App\Http\Middleware\passwordAuth::class,
            'isProductExists' => \App\Http\Middleware\isProductExists::class,
            'CancelExpiredOrders' => \App\Http\Middleware\CancelExpiredOrders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Resource not found' . $e->getMessage()
                ], 404);
            }
        });
    })->create();



// Add this block before return:
/* $app->useStoragePath('/tmp');
 */
return $app;
