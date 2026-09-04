<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hr\Http\Requests\StoreEmployeeContractRequest;
use App\Modules\Hr\Http\Resources\EmployeeContractResource;
use App\Modules\Hr\Models\Employee;
use Illuminate\Http\JsonResponse;

class EmployeeContractController extends Controller
{
    public function index(Employee $employee): JsonResponse
    {
        return EmployeeContractResource::collection(
            $employee->contracts()->orderByDesc('start_date')->get()
        )->response();
    }

    public function store(StoreEmployeeContractRequest $request, Employee $employee): JsonResponse
    {
        // Un nouveau contrat actif clôture les précédents (un seul contrat
        // actif à la fois pour un même employé).
        $employee->contracts()->where('status', 'active')->update(['status' => 'ended']);

        $contract = $employee->contracts()->create([
            ...$request->validated(),
            'currency' => $request->input('currency', 'XOF'),
            'status' => 'active',
        ]);

        return response()->json(new EmployeeContractResource($contract), 201);
    }

    public function terminate(Employee $employee, string $contract): JsonResponse
    {
        $contractModel = $employee->contracts()->findOrFail($contract);
        $contractModel->update(['status' => 'terminated']);

        return response()->json(new EmployeeContractResource($contractModel));
    }
}
