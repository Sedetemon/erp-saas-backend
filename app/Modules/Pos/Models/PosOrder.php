<?php

namespace App\Modules\Pos\Models;

use App\Models\User;
use App\Modules\Hotel\Models\Guest;
use App\Modules\Hotel\Models\Invoice;
use App\Modules\Hotel\Models\Reservation;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosOrder extends Model
{
    use HasUuid, SoftDeletes;

    protected $table = 'pos_orders';

    protected $fillable = [
        'order_number',
        'pos_table_id',
        'guest_id',
        'reservation_id',
        'invoice_id',
        'type',
        'status',
        'payment_method',
        'subtotal',
        'tax_amount',
        'total',
        'created_by',
        'closed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(PosTable::class, 'pos_table_id');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosOrderItem::class);
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
}
