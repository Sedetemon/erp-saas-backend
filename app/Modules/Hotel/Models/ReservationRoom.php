<?php

namespace App\Modules\Hotel\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Database\Factories\Module\Hotel\ReservationRoomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class ReservationRoom extends Model
{
    use HasFactory, HasUuid;
    protected $table = 'reservation_rooms';

    protected $fillable = [
        'reservation_id',
        'room_id',
        'room_type_id',
        'rate_per_night',
        'nights',
        'subtotal',
    ];

    protected $casts = [
        'rate_per_night' => 'decimal:2',
        'nights' => 'integer',
        'subtotal' => 'decimal:2',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    protected static function newFactory(): ReservationRoomFactory
    {
    return ReservationRoomFactory::new();
    }
}
