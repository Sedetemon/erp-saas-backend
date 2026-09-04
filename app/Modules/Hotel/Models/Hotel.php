<?php

namespace App\Modules\Hotel\Models;

use App\Models\Tenant\Address;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Hotel\Models\Reservation;
use App\Modules\Hotel\Models\Room;

class Hotel extends Model
{
    use HasUuid, SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'hotels';

    protected $fillable = [
        'name', 'slug', 'description', 'phone', 'email',
        'website', 'opening_hours', 'settings', 'is_active'
    ];

    protected $casts = [
        'opening_hours' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    // ============================================================
    // RELATIONS
    // ============================================================

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable', 'entity_type', 'entity_id');
    }

    public function primaryAddress(): ?Address
    {
        return $this->addresses()->primary()->first();
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    // ============================================================
    // ACCESSORS (géolocalisation)
    // ============================================================

    public function getLatitudeAttribute(): ?float
    {
        return $this->primaryAddress()?->latitude;
    }

    public function getLongitudeAttribute(): ?float
    {
        return $this->primaryAddress()?->longitude;
    }

    public function getFullAddressAttribute(): ?string
    {
        return $this->primaryAddress()?->full_address;
    }

    public function getGoogleMapsUrlAttribute(): ?string
    {
        return $this->primaryAddress()?->google_maps_url ?? null;
    }

    public function getCoordinatesAttribute(): ?string
    {
        return $this->primaryAddress()?->coordinates ?? null;
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeNearby($query, float $lat, float $lng, float $radius = 10)
    {
        return $query->whereHas('addresses', function ($addressesQuery) use ($lat, $lng, $radius) {
            $addressesQuery->where('is_primary', true)
                ->select('*')
                ->selectRaw(
                    '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
                    [$lat, $lng, $lat]
                )
                ->having('distance', '<=', $radius)
                ->orderBy('distance');
        });
    }

    // ============================================================
    // METHODS
    // ============================================================

    public function hasCoordinates(): bool
    {
        return $this->primaryAddress()?->hasCoordinates() ?? false;
    }

    public function distanceTo(float $lat, float $lng): float
    {
        if (!$this->hasCoordinates()) {
            return PHP_FLOAT_MAX;
        }
        return Address::calculateDistance(
            $this->latitude,
            $this->longitude,
            $lat,
            $lng
        );
    }
}
