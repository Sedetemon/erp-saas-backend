<?php

namespace App\Modules\Pos\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosOrderItem extends Model
{
    use HasUuid;

    protected $table = 'pos_order_items';

    protected $fillable = ['pos_order_id', 'pos_product_id', 'quantity', 'unit_price', 'total', 'notes'];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // 1. Calcul automatique du total de la ligne avant sauvegarde
        static::saving(function (self $item) {
            $item->total = round($item->quantity * $item->unit_price, 2);
        });
        // 2. Recalcul automatique de la commande parent après sauvegarde/suppression
        static::saved(function (self $item) {
            $item->order?->recalculateTotals();
        });

        static::deleted(function (self $item) {
            $item->order?->recalculateTotals();
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PosOrder::class, 'pos_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(PosProduct::class, 'pos_product_id');
    }
}
