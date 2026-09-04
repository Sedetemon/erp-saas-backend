<?php

namespace App\Modules\Hr\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeContract extends Model
{
    use HasUuid;

    protected $table = 'employee_contracts';

    protected $fillable = [
        'employee_id',
        'type',
        'start_date',
        'end_date',
        'salary',
        'currency',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'salary' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
