<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Platform\TenantController;
use App\Http\Controllers\Tenant\Auth\TenantAuthController;

/*
|--------------------------------------------------------------------------
| HEALTH & LANDLORD (GLOBAL)
|--------------------------------------------------------------------------
*/

Route::get('/health', function () {
    return response()->json(['status' => 'Landlord API OK']);
});

Route::post('/platform/tenants', [TenantController::class, 'store']);


/*
|--------------------------------------------------------------------------
| ROUTES TENANT (NÉCESSITENT L'IDENTIFICATION TENANT)
|--------------------------------------------------------------------------
*/

Route::middleware(['identify.tenant'])->group(function () {

    // --- AUTHENTIFICATION TENANT ---
    Route::post('/auth/login', [TenantAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/auth/me', function (Request $request) {
            return response()->json([
                'message'         => 'Authentification réussie',
                'tenant_id'       => tenant()->id,
                'tenant_database' => \DB::connection()->getDatabaseName(),
                'user'            => $request->user(),
            ]);
        });

        Route::post('/auth/logout', [TenantAuthController::class, 'logout']);


        // --- MODULE OPTIONNEL : HÔTELLERIE ---
        Route::middleware(['tenant.module:hotel'])->prefix('hotel')->group(function () {
            Route::get('/rooms', function () {
                return response()->json(['message' => 'Accès autorisé au module Hôtellerie']);
            });
        });


        // --- MODULE OPTIONNEL : RESSOURCES HUMAINES ---
        Route::middleware(['tenant.module:hr'])->prefix('hr')->group(function () {
            Route::get('/employees', function () {
                return response()->json(['message' => 'Accès autorisé au module RH']);
            });
        });


        // --- MODULE OPTIONNEL : INVENTAIRE ---
        Route::middleware(['tenant.module:inventory'])->prefix('inventory')->group(function () {
            Route::get('/stocks', function () {
                return response()->json(['message' => 'Accès autorisé au module Stocks']);
            });
        });

    });
});
