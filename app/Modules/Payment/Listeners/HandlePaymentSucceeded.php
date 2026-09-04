<?php

namespace App\Modules\Payment\Listeners;

use App\Modules\Payment\Events\PaymentSucceeded;
use Illuminate\Support\Facades\Log;

/**
 * TODO : logique métier réelle à implémenter selon $event->transaction->entity_type
 * (reservation, invoice, subscription, order) — mise à jour du solde côté module
 * concerné. Ce listener est un stub qui évite un crash à la résolution de
 * l'événement ; il ne fait pour l'instant que journaliser.
 */
class HandlePaymentSucceeded
{
    public function handle(PaymentSucceeded $event): void
    {
        Log::info('Paiement réussi', [
            'transaction_id' => $event->transaction->id,
            'entity_type'    => $event->transaction->entity_type,
            'entity_id'      => $event->transaction->entity_id,
            'amount'         => $event->transaction->amount,
        ]);
    }
}
