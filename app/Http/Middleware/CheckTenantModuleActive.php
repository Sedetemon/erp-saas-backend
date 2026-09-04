<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantModuleActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $moduleName
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $moduleName): Response
    {
        // 1. Récupération du tenant actuellement identifié par le sous-domaine
        $tenant = tenant();

        if (!$tenant) {
            return response()->json([
                'error' => 'Tenant non identifié.'
            ], 403);
        }

        // 2. Vérification de la présence et de l'activation du module demandé pour ce tenant
        $isModuleActive = $tenant->tenantModules()
            ->whereHas('module', function ($query) use ($moduleName) {
                $query->where('name', $moduleName);
            })
            ->where('is_active', true)
            ->exists();

        if (!$isModuleActive) {
            return response()->json([
                'error' => "Accès refusé. Le module [{$moduleName}] n'est pas activé ou souscrit par votre organisation."
            ], 403);
        }

        return $next($request);
    }
}
