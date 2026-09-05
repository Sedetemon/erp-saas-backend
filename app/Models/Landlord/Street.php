<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Tenant\Address;

class Street extends Model
{
    protected $connection = 'landlord';
    protected $table = 'streets';
    protected $fillable = ['neighborhood_id', 'name', 'slug', 'type', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function neighborhood(): BelongsTo
    {
        return $this->belongsTo(Neighborhood::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
