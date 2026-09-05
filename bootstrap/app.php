<?php

use App\Http\Middleware\EnsureModuleActive;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(
    basePath: dirname(__DIR__)
)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',

        then: function () {
            /*
            |--------------------------------------------------------------------------
            | Routes tenant (séparées des routes centrales de routes/api.php)
            |--------------------------------------------------------------------------
            */
            Route::middleware(['api', IdentifyTenant::class])
                ->prefix('api')
                ->group(base_path('routes/tenant.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {

        // Ajout de l'alias pour le middleware de vérification de module
        $middleware->alias([
            'tenant.module' => \App\Http\Middleware\CheckTenantModuleActive::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Priorité et alias de middleware ERP SaaS
        |--------------------------------------------------------------------------
        */
        $middleware->priority([
            IdentifyTenant::class,
            \Illuminate\Auth\Middleware\Authenticate::class,
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'identify.tenant' => IdentifyTenant::class,
            'module.active'   => EnsureModuleActive::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {

        /*
        |--------------------------------------------------------------------------
        | Gestion globale des exceptions
        |--------------------------------------------------------------------------
        | Reprend la logique de rendu des exceptions métier ERP (ValidationException,
        | BusinessException, ModuleException, etc.) qui n'était jusqu'ici définie
        | que dans app/Exceptions/Handler.php — jamais enregistrée nulle part
        | (Laravel 11+ ignore ce fichier sauf branchement explicite ici).
        */
        $exceptions->render(function (\App\Exceptions\ERPException $exception, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'errors' => $exception->context(),
                ], $exception->getCode());
            }
        });

    })
    ->create();
