<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tenant\StaffResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Liste du personnel (users) du tenant courant. Volontairement en dehors
 * du préfixe /hotel : ce n'est pas propre au module Hôtel, c'est une
 * ressource transversale utilisable par tous les modules métier
 * (housekeeping, POS, futurs modules...).
 */
class StaffController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $staff = User::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($q2) => $q2
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                );
            })
            ->orderBy('name')
            ->paginate(30);

        return StaffResource::collection($staff)->response();
    }

    public function show(User $user): JsonResponse
    {
        return (new StaffResource($user))->response();
    }
}
