<?php

namespace App\Modules\Hr\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasUuid;

    protected $table = 'attendance_records';

    protected $fillable = [
        'employee_id',
        'date',
        'clock_in',
        'clock_out',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isClockedIn(): bool
    {
        return $this->clock_in !== null && $this->clock_out === null;
    }
}
