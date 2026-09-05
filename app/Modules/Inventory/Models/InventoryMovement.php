<?php

namespace App\Modules\Inventory\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasUuid;

    protected $table = 'inventory_movements';

    protected $fillable = [
        'inventory_item_id',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
