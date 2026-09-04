<?php

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Models\Transaction;

class MobileMoneyService
{
    public function initiate(Transaction $transaction, array $data): array
    {
        // Simuler un appel API Orange Money / MTN / Wave
        // Retourner une référence unique
        $reference = 'MM-' . $transaction->id . '-' . now()->timestamp;

        // Logique réelle à implémenter selon le fournisseur
        return ['reference' => $reference];
    }
}
