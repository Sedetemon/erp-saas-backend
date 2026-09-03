<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IdentifyTenant
{
    public function handle(
        Request $request,
        Closure $next
    ) {

        $tenant =
        $request->header('X-Tenant');

        if (! $tenant) {

            abort(400,
                'Tenant manquant');

        }

        return $next($request);

    }
}
