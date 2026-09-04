<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hr\Http\Requests\StoreEmployeeRequest;
use App\Modules\Hr\Http\Resources\EmployeeResource;
use App\Modules\Hr\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $employees = Employee::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('department'), fn ($q) => $q->where('department', $request->string('department')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($q2) => $q2
                    ->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                );
            })
            ->orderBy('last_name')
            ->paginate(20);

        return EmployeeResource::collection($employees)->response();
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = Employee::create($request->validated());

        return response()->json(new EmployeeResource($employee), 201);
    }

    public function show(Employee $employee): JsonResponse
    {
        $employee->load('contracts');

        return response()->json(new EmployeeResource($employee));
    }

    public function update(StoreEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $employee->update($request->validated());

        return response()->json(new EmployeeResource($employee));
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $employee->delete();

        return response()->json(null, 204);
    }
}
