<?php

namespace App\Modules\Hr\Models;

use App\Models\User;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasUuid, SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'position',
        'department',
        'hire_date',
        'status',
        'date_of_birth',
        'national_id',
        'address',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'date_of_birth' => 'date',
    ];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class);
    }

    public function activeContract(): ?EmployeeContract
    {
        return $this->contracts()->where('status', 'active')->latest('start_date')->first();
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
