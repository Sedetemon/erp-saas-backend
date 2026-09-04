<?php

namespace App\Modules\Payment\Listeners;

use App\Modules\Payment\Events\PaymentFailed;
use Illuminate\Support\Facades\Log;

/**
 * TODO : logique métier réelle à implémenter (notification, relance, etc.)
 * Stub actuel : journalisation uniquement, pour éviter un crash à la
 * résolution de l'événement.
 */
class HandlePaymentFailed
{
    public function handle(PaymentFailed $event): void
    {
        Log::warning('Paiement échoué', [
            'transaction_id' => $event->transaction->id,
            'entity_type'    => $event->transaction->entity_type,
            'entity_id'      => $event->transaction->entity_id,
        ]);
    }
}
