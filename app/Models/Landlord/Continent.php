<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Continent extends Model
{
    protected $connection = 'landlord';
    protected $table = 'continents';
    protected $fillable = ['code', 'name', 'slug', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function countries(): HasMany
    {
        return $this->hasMany(Country::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
