<?php

namespace App\Modules\Hotel\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestLedger extends Model
{
    use HasUuids;

    protected $fillable = [
        'reservation_id',
        'guest_id',
        'type',
        'source',
        'source_id',
        'description',
        'amount',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function hotel(): BelongsTo
{
    return $this->belongsTo(Hotel::class);
}
}
