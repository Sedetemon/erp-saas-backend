<?php

namespace App\Modules\Hotel\Services;

use App\Modules\Hotel\Models\Guest;
use App\Modules\Hotel\Models\Payment;
use App\Modules\Hotel\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReservationService
{
    /**
     * Création d'une réservation et enregistrement dans la table pivot reservation_rooms.
     */
    public function createReservation(
    Guest $guest,
    \DateTimeInterface $checkIn,
    \DateTimeInterface $checkOut,
    array $roomBookings,
    string $source = 'direct',
    ?string $createdBy = null
): Reservation {
    return DB::transaction(function () use ($guest, $checkIn, $checkOut, $roomBookings, $source, $createdBy) {
        $start  = Carbon::instance($checkIn);
        $end    = Carbon::instance($checkOut);
        $nights = max(1, $start->diffInDays($end));

        $totalAmount = 0;

        $reservation = Reservation::create([
            'id'                 => (string) Str::uuid(),
            'reservation_number' => 'RES-' . strtoupper(Str::random(6)),
            'guest_id'           => $guest->id,
            'check_in_date'      => $start->toDateString(),
            'check_out_date'     => $end->toDateString(),
            'status'             => 'confirmed',
            'source'             => $source,
            'created_by'         => $createdBy,
        ]);

        foreach ($roomBookings as $booking) {
            $rate     = (float) $booking['rate_per_night'];
            $subtotal = $rate * $nights;
            $totalAmount += $subtotal;

            DB::table('reservation_rooms')->insert([
                'id'             => (string) Str::uuid(),
                'reservation_id' => $reservation->id,
                'room_id'        => $booking['room_id'] ?? null,
                'room_type_id'   => $booking['room_type_id'],
                'rate_per_night' => $rate,
                'nights'         => $nights,
                'subtotal'       => $subtotal,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        // Charge initiale inscrite au ledger du client
        $reservation->ledgers()->create([
            'id'          => (string) Str::uuid(),
            'guest_id'    => $guest->id,
            'type'        => 'charge',
            'source'      => 'reservation',
            'source_id'   => $reservation->id,
            'description' => "Hébergement {$nights} nuit(s)",
            'amount'      => $totalAmount,
        ]);

        $reservation->update([
            'total'   => $totalAmount,
            'balance' => $totalAmount,
        ]);

        return $reservation->fresh();
    });
}
    /**
     * Effectue le check-in de la réservation et passe les chambres en 'occupied'.
     */
    public function checkIn(Reservation $reservation): Reservation
    {
        return DB::transaction(function () use ($reservation) {
            $reservation->update([
                'status'        => 'checked_in',
                'actual_check_in' => now(),
            ]);

            // Mise à jour du statut des chambres assignées
            $roomIds = $reservation->reservationRooms()->pluck('room_id')->filter();
            if ($roomIds->isNotEmpty()) {
                DB::table('rooms')->whereIn('id', $roomIds)->update(['status' => 'occupied']);
            }

            return $reservation;
        });
    }

    /**
     * Check-out : Libère les chambres, bascule en 'dirty' (à nettoyer) et solde la facture si demandé.
     */
    public function checkOut(Reservation $reservation, ?string $paymentMethod = null): Reservation
{
    return DB::transaction(function () use ($reservation, $paymentMethod) {
        if ($paymentMethod && $reservation->balance > 0) {
            $this->addPayment($reservation, $reservation->balance, $paymentMethod, 'Règlement Check-out');
        }

        $reservation->update([
            'status'           => 'checked_out',
            'actual_check_out' => now(),
        ]);

        $roomIds = $reservation->reservationRooms()->pluck('room_id')->filter();
        if ($roomIds->isNotEmpty()) {
            DB::table('rooms')->whereIn('id', $roomIds)->update(['status' => 'cleaning']);
        }

        return $reservation;
    });
}

    /**
     * Annulation de la réservation et libération des chambres.
     */
    public function cancel(Reservation $reservation): Reservation
    {
        return DB::transaction(function () use ($reservation) {
            $reservation->update(['status' => 'cancelled']);

            $roomIds = $reservation->reservationRooms()->pluck('room_id')->filter();
            if ($roomIds->isNotEmpty()) {
                DB::table('rooms')->whereIn('id', $roomIds)->update(['status' => 'available']);
            }

            return $reservation;
        });
    }

    /**
     * Ajout d'un paiement (acompte ou règlement) au folio du séjour.
     */
    public function addPayment(
    Reservation $reservation,
    float $amount,
    string $paymentMethod,
    ?string $reference = null
): Payment {
    return DB::transaction(function () use ($reservation, $amount, $paymentMethod, $reference) {
        $payment = Payment::create([
            'id'             => (string) Str::uuid(),
            'reservation_id' => $reservation->id,
            'guest_id'       => $reservation->guest_id,
            'amount'         => $amount,
            'method'         => $paymentMethod,
            'reference'      => $reference,
            'paid_at'        => now(),
        ]);

        // Le ledger 'payment' est déjà créé automatiquement par Payment::booted().
        // Ne pas dupliquer ici.

        $reservation->update([
            'balance' => $reservation->fresh()->balance,
        ]);

        return $payment;
    });
}

    /**
     * Chambres d'un type donné, disponibles sur la période demandée.
     *
     * Réutilise la même logique de chevauchement que la règle de validation
     * RoomIsAvailable, pour garantir que la disponibilité affichée ici
     * correspond exactement à ce que la création de réservation acceptera.
     */
    public function availableRooms(
        \App\Modules\Hotel\Models\RoomType $roomType,
        \DateTimeInterface $checkIn,
        \DateTimeInterface $checkOut
    ): \Illuminate\Support\Collection {
        $checkInDate = Carbon::instance($checkIn)->toDateString();
        $checkOutDate = Carbon::instance($checkOut)->toDateString();

        $bookedRoomIds = DB::table('reservation_rooms')
            ->join('reservations', 'reservations.id', '=', 'reservation_rooms.reservation_id')
            ->whereNotIn('reservations.status', ['cancelled', 'checked_out'])
            ->where('reservations.check_in_date', '<', $checkOutDate)
            ->where('reservations.check_out_date', '>', $checkInDate)
            ->pluck('reservation_rooms.room_id')
            ->filter();

        return \App\Modules\Hotel\Models\Room::query()
            ->where('room_type_id', $roomType->id)
            ->where('status', '!=', 'maintenance')
            ->whereNotIn('id', $bookedRoomIds)
            ->orderBy('number')
            ->get();
    }
}
