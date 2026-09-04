<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hr\Http\Requests\StoreLeaveRequestRequest;
use App\Modules\Hr\Http\Resources\LeaveRequestResource;
use App\Modules\Hr\Models\Employee;
use App\Modules\Hr\Models\LeaveRequest;
use App\Modules\Hr\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function __construct(protected LeaveService $leaves)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $leaves = LeaveRequest::with(['employee', 'approvedBy'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->string('employee_id')))
            ->orderByDesc('start_date')
            ->paginate(20);

        return LeaveRequestResource::collection($leaves)->response();
    }

    public function store(StoreLeaveRequestRequest $request, Employee $employee): JsonResponse
    {
        $start = Carbon::parse($request->input('start_date'));
        $end = Carbon::parse($request->input('end_date'));

        $leave = $employee->leaveRequests()->create([
            ...$request->validated(),
            'days_count' => $start->diffInDays($end) + 1,
            'status' => 'pending',
        ]);

        return response()->json(new LeaveRequestResource($leave->load('employee')), 201);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $leave = $this->leaves->approve($leaveRequest, $request->user());

        return response()->json(new LeaveRequestResource($leave->load(['employee', 'approvedBy'])));
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $leave = $this->leaves->reject($leaveRequest, $request->user());

        return response()->json(new LeaveRequestResource($leave->load(['employee', 'approvedBy'])));
    }

    public function cancel(LeaveRequest $leaveRequest): JsonResponse
    {
        $leave = $this->leaves->cancel($leaveRequest);

        return response()->json(new LeaveRequestResource($leave->load('employee')));
    }
}
