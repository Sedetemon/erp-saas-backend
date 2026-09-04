<?php

namespace App\Http\Middleware;

use App\Models\Landlord\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        /*
        |--------------------------------------------------------------------------
        | 1. Lecture du tenant depuis le header
        |--------------------------------------------------------------------------
        */

        $tenantSlug = $request->header('X-Tenant');

        if (!$tenantSlug) {
            return response()->json([
                'message' => 'Le header X-Tenant est requis.'
            ], 400);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Recherche du tenant dans la base centrale (landlord)
        |--------------------------------------------------------------------------
        */

        $tenant = Tenant::on('landlord')
            ->where('slug', $tenantSlug)
            ->first();

        if (!$tenant) {
            return response()->json([
                'message' => 'Tenant introuvable.'
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Initialisation Stancl Tenancy
        |--------------------------------------------------------------------------
        |
        | Initialise l'environnement du tenant et reconfigure la connexion
        | de base de données par défaut de Laravel vers la DB du tenant.
        |
        */

        tenancy()->initialize($tenant);

        // Alias explicite si vos modèles pointent spécifiquement sur protected $connection = 'tenant';
        $defaultConn = config('database.default');
        if (!config("database.connections.tenant")) {
            config(["database.connections.tenant" => config("database.connections.{$defaultConn}")]);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Continuer vers le middleware suivant
        |--------------------------------------------------------------------------
        */

        return $next($request);
    }
}
