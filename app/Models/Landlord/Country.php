<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $connection = 'landlord';
    protected $table = 'countries';
    protected $fillable = ['continent_id', 'code', 'name', 'slug', 'phone_code', 'currency_code', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function continent(): BelongsTo
    {
        return $this->belongsTo(Continent::class);
    }

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
