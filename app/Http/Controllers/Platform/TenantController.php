<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\TenantProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function __construct(
        protected TenantProvisioningService $provisioningService
    ) {}

    /**
     * Création d'un tenant depuis l'API plateforme.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                'unique:landlord.tenants,slug',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],
            'plan_id' => [
                'nullable',
                'exists:landlord.plans,id',
            ],
            'modules' => [
                'nullable',
                'array',
            ],
            'modules.*' => [
                'string',
                'exists:landlord.modules,name',
            ],
        ]);

        $tenant = $this->provisioningService->provision(
            $validated,
            $validated['modules'] ?? []
        );

        // Rechargement des modules rattachés pour le retour JSON
        $activeModules = $tenant->tenantModules()
            ->where('is_active', true)
            ->with('module')
            ->get()
            ->pluck('module.name')
            ->filter()
            ->values();

        return response()->json([
            'message' => 'Tenant créé avec succès.',
            'tenant'  => [
                'id'       => $tenant->id,
                'name'     => $tenant->name,
                'slug'     => $tenant->slug,
                'email'    => $tenant->email,
                'status'   => $tenant->status,
                'database' => $tenant->tenancy_db_name,
                'modules'  => $activeModules,
            ],
        ], 201);
    }
}
