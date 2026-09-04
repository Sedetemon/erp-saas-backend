<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleActive
{
    /**
     * Usage : ->middleware('module.active:hotel')
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $tenant = tenant();

        if (! $tenant || ! $tenant->hasModule($module)) {
            abort(403, "Le module \"{$module}\" n'est pas activé pour ce tenant.");
        }

        return $next($request);
    }
}
