<?php

namespace App\Modules\Hotel\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuid;

    protected $table = 'payments';

    protected $fillable = [
        'invoice_id',
        'reservation_id',
        'guest_id',
        'amount',
        'method',
        'reference',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (self $payment) {
            $invoice = $payment->invoice;
            $reservationId = $payment->reservation_id ?? $invoice?->reservation_id;
            $guestId = $payment->guest_id ?? $invoice?->guest_id;

            // 1. Inscription unique du crédit dans le Guest Ledger
            if ($reservationId && $guestId) {
                GuestLedger::create([
                    'reservation_id' => $reservationId,
                    'guest_id' => $guestId,
                    'type' => 'payment',
                    'source' => 'payment',
                    'source_id' => $payment->id,
                    'description' => 'Paiement reçu ('.strtoupper($payment->method).')'.($payment->reference ? " — Ref: {$payment->reference}" : ''),
                    'amount' => $payment->amount,
                ]);
            }

            // 2. Clôture automatique de la Facture
            if ($invoice && $reservationId) {
                $reservation = Reservation::find($reservationId);
                if ($reservation && $reservation->fresh()->balance <= 0 && $invoice->status !== 'paid') {
                    $invoice->update([
                        'status' => 'paid',
                        'paid_at' => $payment->paid_at ?? now(),
                    ]);
                }
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function hotel(): BelongsTo
{
    return $this->belongsTo(Hotel::class);
}
}
