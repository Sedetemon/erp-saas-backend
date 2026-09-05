<?php

namespace App\Modules\Hotel\Models;

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
        'nationality',
        'document_type',
        'document_number',
        'address',
        'notes',
    ];

    protected static function newFactory(): GuestFactory
    {
        return GuestFactory::new();
    }

    // --- RELATIONS ---

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // --- ACCESSEURS ---

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
