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

        // Les routes métier des modules Hôtel / RH / Inventaire / POS / Messaging
        // sont définies dans routes/tenant.php (chargé plus bas dans bootstrap/app.php),
        // avec le middleware module.active:<nom>. Ne pas redéfinir de stubs ici :
        // toute route dupliquée avec la même URI serait simplement écrasée par
        // celle de tenant.php, ce qui rend ce fichier trompeur à la lecture.
    });
});
