<?php

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Models\Transaction;

class CardPaymentService
{
    public function initiate(Transaction $transaction, array $data): array
    {
        // Simuler un appel Stripe / Paypal
        $reference = 'CARD-' . $transaction->id . '-' . now()->timestamp;

        return ['reference' => $reference];
    }
}
