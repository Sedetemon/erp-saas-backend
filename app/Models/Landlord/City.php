<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $connection = 'landlord';
    protected $table = 'cities';
    protected $fillable = ['department_id', 'name', 'slug', 'postal_code', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function neighborhoods(): HasMany
    {
        return $this->hasMany(Neighborhood::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
