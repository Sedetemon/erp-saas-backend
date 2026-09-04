<?php

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Events\PaymentFailed;
use App\Modules\Payment\Events\PaymentSucceeded;
use App\Modules\Payment\Models\Transaction;
use App\Modules\Payment\Models\WebhookLog;

class WebhookService
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function log(string $provider, array $payload): void
    {
        WebhookLog::create([
            'provider'   => $provider,
            'event_type' => $payload['event'] ?? 'unknown',
            'payload'    => $payload,
            'status'     => 'received',
        ]);
    }

    public function process(string $provider, array $payload): array
    {
        return match ($provider) {
            'orange_money', 'mtn_money', 'wave', 'stripe', 'card', 'manual' => $this->processExternal($provider, $payload),
            default => ['status' => 'ignored', 'message' => 'Provider not supported']
        };
    }

    protected function processExternal(string $provider, array $payload): array
    {
        $reference = $payload['reference'] ?? $payload['transaction_id'] ?? null;
        if (! $reference) {
            return ['status' => 'error', 'message' => 'No reference provided'];
        }

        $transaction = Transaction::where('provider', $provider)
            ->where('provider_reference', $reference)
            ->first();

        if (! $transaction) {
            return ['status' => 'ok', 'message' => 'Transaction not found'];
        }

        if (($payload['status'] ?? null) === 'succeeded') {
            $transaction->markAsPaid();
            event(new PaymentSucceeded($transaction));

            return ['status' => 'ok'];
        } else {
            $transaction->markAsFailed($payload['reason'] ?? 'Unknown');
            event(new PaymentFailed($transaction));

            return ['status' => 'ok'];
        }
    }
}
