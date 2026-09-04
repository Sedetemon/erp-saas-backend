<?php

namespace App\Modules\Hotel\Models;

use App\Modules\Geography\Models\City;
use App\Modules\Geography\Models\Country;
use App\Support\Traits\HasUuid;
use Database\Factories\Module\Hotel\GuestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guest extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $table = 'guests';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'country_id',
        'city_id',
        'nationality',
        'document_type',
        'document_number',
        'address',
        'notes',
        'hotel_id',
    ];

    protected static function newFactory(): GuestFactory
    {
        return GuestFactory::new();
    }

    // --- RELATIONS ---

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    // --- ACCESSEURS ---

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
