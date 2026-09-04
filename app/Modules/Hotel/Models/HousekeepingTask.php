<?php

namespace App\Modules\Hotel\Models;

use App\Models\User;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HousekeepingTask extends Model
{
    use HasUuid, SoftDeletes;

    protected $table = 'housekeeping_tasks';

    protected $fillable = [
        'room_id',
        'assigned_to',
        'created_by',
        'type',
        'status',
        'notes',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function start(): static
    {
        $this->update(['status' => 'in_progress', 'started_at' => now()]);

        return $this;
    }

    /**
     * Termine la tâche et, pour un nettoyage, remet la chambre disponible.
     */
    public function complete(): static
    {
        $this->update(['status' => 'completed', 'completed_at' => now()]);

        if (in_array($this->type, ['checkout_cleaning', 'daily_cleaning'], true)) {
            $this->room->update(['status' => 'available']);
        }

        return $this;
    }

    public function hotel(): BelongsTo
{
    return $this->belongsTo(Hotel::class);
}
}
