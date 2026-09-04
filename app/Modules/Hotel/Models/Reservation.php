<?php

namespace App\Modules\Hotel\Models;

use App\Models\User;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Database\Factories\Module\Hotel\ReservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reservation extends Model
{
   use HasFactory, HasUuid, SoftDeletes;

    protected $table = 'reservations';

    protected $fillable = [
        'reservation_number',
        'guest_id',
        'check_in_date',
        'check_out_date',
        'adults',
        'children',
        'status',
        'actual_check_in',  // ⚠️ Ajouté
        'actual_check_out', // ⚠️ Ajouté
        'source',
        'total',     // ⚠️ Requis pour l'assignation en masse
        'balance',   // ⚠️ Requis pour l'assignation en masse
        'notes',
        'created_by',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'actual_check_in'  => 'datetime', // ⚠️ Ajouté
        'actual_check_out' => 'datetime', // ⚠️ Ajouté
        'adults' => 'integer',
        'children' => 'integer',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }


    public function reservationRooms(): HasMany
    {
        return $this->hasMany(ReservationRoom::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(GuestLedger::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function nights(): int
    {
        return (int) $this->check_in_date->diffInDays($this->check_out_date);
    }

    protected static function newFactory(): ReservationFactory
{
    return ReservationFactory::new();
}

    /**
     * Total réellement dû sur le séjour : toutes les charges du ledger
     * (chambres + POS facturé sur la chambre + toute future source de
     * charge), PAS seulement les chambres. Aligné sur balance() pour que
     * balance = total - paiements soit toujours vrai, sans quoi les deux
     * chiffres peuvent se contredire dès qu'il y a une charge hors chambre
     * (ex: total=75000 mais balance=79500 à cause d'une commande POS).
     */
    public function total(): float
    {
        return (float) $this->ledgers()->where('type', 'charge')->sum('amount');
    }

    /**
     * Renvoie le solde actuel du séjour : (Total Charges) - (Total Paiements).
     * Un solde > 0 signifie que le client doit de l'argent.
     */
    public function getBalanceAttribute(): float
    {
        $charges = $this->ledgers()->where('type', 'charge')->sum('amount');
        $credits = $this->ledgers()->whereIn('type', ['payment', 'discount'])->sum('amount');

        return (float) ($charges - $credits);
    }
}
