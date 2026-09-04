<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $connection = 'landlord';
    protected $table = 'regions';
    protected $fillable = ['country_id', 'code', 'name', 'slug', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
