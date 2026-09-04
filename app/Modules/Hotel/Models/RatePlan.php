<?php

namespace App\Modules\Hotel\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatePlan extends Model
{
    use HasUuid;

    protected $table = 'rate_plans';

    protected $fillable = [
        'room_type_id',
        'name',
        'price_per_night',
        'starts_on',
        'ends_on',
        'is_default',
    ];

    protected $casts = [
        'price_per_night' => 'decimal:2',
        'starts_on' => 'date',
        'ends_on' => 'date',
        'is_default' => 'boolean',
    ];

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function hotel(): BelongsTo
{
    return $this->belongsTo(Hotel::class);
}
}
