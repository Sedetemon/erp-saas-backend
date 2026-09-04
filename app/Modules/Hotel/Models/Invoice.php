<?php

namespace App\Modules\Hotel\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasUuid, SoftDeletes;

    protected $table = 'invoices';

    protected $fillable = [
        'invoice_number',
        'reservation_id',
        'guest_id',
        'status',
        'subtotal',
        'tax_amount',
        'total',
        'issued_at',
        'paid_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function amountPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function balanceDue(): float
    {
        return (float) $this->total - $this->amountPaid();
    }

    public function recalculateTotals(float $taxRate = 0.0): void
    {
        $subtotal = (float) $this->items()->sum('total');
        $tax = $subtotal * $taxRate;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total' => $subtotal + $tax,
        ]);
    }
    public function hotel(): BelongsTo
{
    return $this->belongsTo(Hotel::class);
}
}
