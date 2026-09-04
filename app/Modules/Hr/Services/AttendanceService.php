<?php

namespace App\Modules\Hr\Services;

use App\Modules\Hr\Models\AttendanceRecord;
use App\Modules\Hr\Models\Employee;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Pointage d'entrée. Un seul enregistrement par employé et par jour
     * (contrainte unique en base) : si un pointage existe déjà pour
     * aujourd'hui sans sortie, on le retourne tel quel plutôt que d'échouer.
     */
    public function clockIn(Employee $employee): AttendanceRecord
    {
        return DB::transaction(function () use ($employee) {
            $today = now()->toDateString();

            $existing = AttendanceRecord::where('employee_id', $employee->id)
                ->where('date', $today)
                ->first();

            if ($existing) {
                return $existing;
            }

            return AttendanceRecord::create([
                'employee_id' => $employee->id,
                'date' => $today,
                'clock_in' => now(),
                'status' => now()->format('H:i') > '09:15' ? 'late' : 'present',
            ]);
        });
    }

    public function clockOut(Employee $employee): ?AttendanceRecord
    {
        $today = now()->toDateString();

        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if (! $record || $record->clock_out !== null) {
            return $record;
        }

        $record->update(['clock_out' => now()]);

        return $record->fresh();
    }
}
