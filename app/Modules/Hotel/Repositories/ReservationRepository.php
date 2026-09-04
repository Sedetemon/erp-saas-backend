<?php

namespace App\Modules\Hotel\Repositories;

use App\Modules\Hotel\Models\Reservation;
use App\Modules\Hotel\Models\ReservationRoom;
use Illuminate\Support\Str;

class ReservationRepository
{
    /**
     * Génère un numéro de réservation unique (ex: RES-20260824-A8F3).
     */
    public function generateReservationNumber(): string
    {
        $prefix = 'RES-' . now()->format('Ymd') . '-';
        return $prefix . strtoupper(Str::random(4));
    }

    /**
     * Crée l'enregistrement principal de la réservation.
     */
    public function create(array $data): Reservation
    {
        return Reservation::create([
            'reservation_number' => $this->generateReservationNumber(),
            'guest_id'           => $data['guest_id'],
            'check_in_date'      => $data['check_in_date'],
            'check_out_date'     => $data['check_out_date'],
            'adults'             => $data['adults'] ?? 1,
            'children'           => $data['children'] ?? 0,
            'status'             => $data['status'] ?? 'confirmed',
            'source'             => $data['source'] ?? 'walk_in',
            'notes'              => $data['notes'] ?? null,
            'total'              => $data['total'],
            'balance'            => $data['total'],
        ]);
    }

    /**
     * Rattache une chambre/type de chambre à la réservation.
     */
    public function attachRoom(Reservation $reservation, array $roomData, int $nights): ReservationRoom
    {
        $subtotal = $roomData['rate_per_night'] * $nights;

        return $reservation->rooms()->create([
            'room_id'        => $roomData['room_id'] ?? null,
            'room_type_id'   => $roomData['room_type_id'],
            'rate_per_night' => $roomData['rate_per_night'],
            'nights'         => $nights,
            'subtotal'       => $subtotal,
        ]);
    }
}
