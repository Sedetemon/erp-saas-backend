<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Address extends Model
{
    protected $connection = 'tenant';
    protected $table = 'addresses';

    protected $fillable = [
        'entity_id', 'entity_type', 'street_name', 'street_number',
        'building', 'floor', 'apartment', 'neighborhood_name',
        'city_name', 'additional_info', 'latitude', 'longitude',
        'is_primary', 'is_billing', 'is_delivery'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_billing' => 'boolean',
        'is_delivery' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7'
    ];

    public function addressable(): MorphTo
    {
        return $this->morphTo('addressable', 'entity_type', 'entity_id');
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeForEntity($query, string $entityType, string $entityId)
    {
        return $query->where('entity_type', $entityType)
            ->where('entity_id', $entityId);
    }

    public function getFullAddressAttribute(): string
    {
        $parts = [];
        if ($this->street_number) $parts[] = $this->street_number;
        if ($this->street_name) $parts[] = $this->street_name;
        if ($this->building) $parts[] = 'Bâtiment ' . $this->building;
        if ($this->floor) $parts[] = 'Étage ' . $this->floor;
        if ($this->apartment) $parts[] = 'Appartement ' . $this->apartment;
        if ($this->neighborhood_name) $parts[] = $this->neighborhood_name;
        if ($this->city_name) $parts[] = $this->city_name;
        if ($this->additional_info) $parts[] = $this->additional_info;
        return implode(', ', $parts);
    }

    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    public static function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    public function distanceTo(float $lat, float $lng): float
    {
        if (!$this->hasCoordinates()) {
            return PHP_FLOAT_MAX;
        }
        return self::calculateDistance($this->latitude, $this->longitude, $lat, $lng);
    }
}
