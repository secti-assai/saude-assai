<?php

use App\Http\Middleware\EnsureApiKey;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\VerifyGovAssaiWebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'api.key' => EnsureApiKey::class,
            'webhook.govassai' => VerifyGovAssaiWebhookSignature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $e) {
            Log::error('Unhandled application exception', [
                'type' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (app()->environment('production') && ! $request->expectsJson()) {
                return response()->view('errors.generic', [], 500);
            }

            return null;
        });
    })->create();
