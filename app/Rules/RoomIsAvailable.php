<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class RoomIsAvailable implements ValidationRule
{
    public function __construct(
        protected ?string $checkInDate,
        protected ?string $checkOutDate,
        protected ?string $ignoreReservationId = null
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Si aucune chambre spécifique n'est sélectionnée, la validation passe
        if (blank($value)) {
            return;
        }

        // Si les dates sont absentes, on laisse les autres règles gérer
        if (blank($this->checkInDate) || blank($this->checkOutDate)) {
            return;
        }

        // Vérification des chevauchements de dates
        $hasOverlap = DB::table('reservation_rooms')
            ->join('reservations', 'reservations.id', '=', 'reservation_rooms.reservation_id')
            ->where('reservation_rooms.room_id', $value)
            ->whereNotIn('reservations.status', ['cancelled', 'checked_out'])
            ->when($this->ignoreReservationId, function ($query) {
                $query->where('reservations.id', '!=', $this->ignoreReservationId);
            })
            ->where(function ($query) {
                $query->where('reservations.check_in_date', '<', $this->checkOutDate)
                      ->where('reservations.check_out_date', '>', $this->checkInDate);
            })
            ->exists();

        if ($hasOverlap) {
            $fail("La chambre sélectionnée est déjà réservée sur cette période.");
        }
    }
}
