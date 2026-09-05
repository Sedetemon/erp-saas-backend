<?php

namespace App\Modules\Inventory\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStock extends Model
{
    use HasUuid;

    protected $table = 'inventory_stocks';

    protected $fillable = [
        'inventory_item_id',
        'quantity',
        'alert_threshold',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'alert_threshold' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function isLow(): bool
    {
        return (float) $this->quantity <= (float) $this->alert_threshold;
    }

    public function hasSufficientQuantity(float $quantity): bool
    {
        return (float) $this->quantity >= $quantity;
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'alert_threshold');
    }
}
