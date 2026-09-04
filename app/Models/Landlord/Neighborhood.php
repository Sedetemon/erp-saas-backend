<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Neighborhood extends Model
{
    protected $connection = 'landlord';
    protected $table = 'neighborhoods';
    protected $fillable = ['city_id', 'name', 'slug', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function streets(): HasMany
    {
        return $this->hasMany(Street::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
