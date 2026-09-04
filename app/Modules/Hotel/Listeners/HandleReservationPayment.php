<?php

namespace App\Modules\Hotel\Listeners;

use App\Modules\Payment\Events\PaymentSucceeded;
use App\Modules\Hotel\Models\Reservation;

class HandleReservationPayment
{
    public function handle(PaymentSucceeded $event): void
    {
        $transaction = $event->transaction;
        if ($transaction->entity_type === 'reservation') {
            $reservation = Reservation::find($transaction->entity_id);
            if ($reservation) {
                $reservation->update(['status' => 'confirmed']);
            }
        }
    }
}
