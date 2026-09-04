<?php

namespace App\Modules\Hr\Services;

use App\Modules\Hr\Models\LeaveRequest;
use App\Models\User;

class LeaveService
{
    public function approve(LeaveRequest $leave, User $approver): LeaveRequest
    {
        $leave->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        return $leave->fresh();
    }

    public function reject(LeaveRequest $leave, User $approver): LeaveRequest
    {
        $leave->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        return $leave->fresh();
    }

    public function cancel(LeaveRequest $leave): LeaveRequest
    {
        $leave->update(['status' => 'cancelled']);

        return $leave->fresh();
    }
}
