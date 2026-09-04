<?php

namespace App\Modules\Hotel\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Database\Factories\Module\Hotel\RoomFactory;

class Room extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $table = 'rooms';

    protected $fillable = [
        'room_type_id',
        'number', // 👈 Remplacez par 'room_number' si c'est le nom dans votre migration
        'floor',
        'status', // 'available', 'occupied', 'maintenance', 'cleaning'
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'floor'     => 'integer',
    ];

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    protected static function newFactory(): RoomFactory
{
    return RoomFactory::new();
}
}
