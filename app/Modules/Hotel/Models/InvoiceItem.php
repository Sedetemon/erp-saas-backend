<?php

namespace App\Modules\Hotel\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasUuid;

    protected $table = 'invoice_items';

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $item->total = round($item->quantity * $item->unit_price, 2);
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
