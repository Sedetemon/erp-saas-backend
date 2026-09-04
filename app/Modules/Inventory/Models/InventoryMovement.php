<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Pos\Models\PosProduct;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasUuid;

    protected $table = 'inventory_movements';

    protected $fillable = [
        'pos_product_id',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(PosProduct::class, 'pos_product_id');
    }
}
