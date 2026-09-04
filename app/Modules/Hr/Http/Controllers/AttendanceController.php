<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hr\Http\Resources\AttendanceRecordResource;
use App\Modules\Hr\Models\AttendanceRecord;
use App\Modules\Hr\Models\Employee;
use App\Modules\Hr\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(protected AttendanceService $attendance)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $records = AttendanceRecord::with('employee')
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->string('employee_id')))
            ->when($request->filled('date'), fn ($q) => $q->where('date', $request->string('date')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('date')
            ->paginate(30);

        return AttendanceRecordResource::collection($records)->response();
    }

    public function clockIn(Employee $employee): JsonResponse
    {
        $record = $this->attendance->clockIn($employee);

        return response()->json(new AttendanceRecordResource($record->load('employee')));
    }

    public function clockOut(Employee $employee): JsonResponse
    {
        $record = $this->attendance->clockOut($employee);

        if (! $record) {
            return response()->json(['message' => "Aucun pointage d'entrée trouvé pour aujourd'hui."], 422);
        }

        return response()->json(new AttendanceRecordResource($record->load('employee')));
    }
}
