<?php

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Models\Transaction;
use App\Modules\Payment\Models\PaymentMethod;
use App\Modules\Payment\Events\PaymentSucceeded;
use App\Modules\Payment\Events\PaymentFailed;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    protected MobileMoneyService $mobileMoneyService;
    protected CardPaymentService $cardPaymentService;

    public function __construct(
        MobileMoneyService $mobileMoneyService,
        CardPaymentService $cardPaymentService
    ) {
        $this->mobileMoneyService = $mobileMoneyService;
        $this->cardPaymentService = $cardPaymentService;
    }

    /**
     * Initier un paiement
     */
    public function initiatePayment(array $data): Transaction
    {
        $transaction = Transaction::create([
            'tenant_id' => $data['tenant_id'],
            'entity_type' => $data['entity_type'],
            'entity_id' => $data['entity_id'],
            'provider' => $data['provider'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'XOF',
            'status' => 'pending',
            'meta' => $data['meta'] ?? [],
        ]);

        $providerResponse = match ($data['provider']) {
            'orange_money', 'mtn_money', 'wave' => $this->mobileMoneyService->initiate($transaction, $data),
            'stripe', 'card' => $this->cardPaymentService->initiate($transaction, $data),
            'manual' => $this->handleManualPayment($transaction, $data),
            default => throw new \Exception('Fournisseur de paiement non supporté')
        };

        $transaction->update([
            'provider_reference' => $providerResponse['reference'] ?? null,
            'meta' => array_merge($transaction->meta ?? [], ['provider_response' => $providerResponse])
        ]);

        return $transaction;
    }

    /**
     * Confirmer un paiement (webhook)
     */
    public function confirmPayment(string $provider, string $reference, array $data): void
    {
        $transaction = Transaction::where('provider', $provider)
            ->where('provider_reference', $reference)
            ->firstOrFail();

        if ($data['status'] === 'succeeded') {
            $transaction->markAsPaid();
            event(new PaymentSucceeded($transaction));
        } else {
            $transaction->markAsFailed($data['reason'] ?? null);
            event(new PaymentFailed($transaction));
        }
    }

    /**
     * Paiement manuel (enregistré par l'admin)
     */
    protected function handleManualPayment(Transaction $transaction, array $data): array
    {
        return ['reference' => 'manual-' . $transaction->id];
    }

    public function storePaymentMethod(string $userId, string $provider, array $data): PaymentMethod
{
    $method = PaymentMethod::create([
        'tenant_id' => tenant()->id,
        'user_id' => $userId,
        'provider' => $provider,
        'provider_token' => $data['token'] ?? null,
        'last_four' => $data['last_four'] ?? null,
        'brand' => $data['brand'] ?? null,
        'is_default' => $data['is_default'] ?? false,
        'meta' => $data['meta'] ?? [],
    ]);

    // Si c'est le premier mode de paiement, le mettre par défaut
    if ($method->is_default) {
        PaymentMethod::where('user_id', $userId)
            ->where('id', '!=', $method->id)
            ->update(['is_default' => false]);
    }

    return $method;
}
}
