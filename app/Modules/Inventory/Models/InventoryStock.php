<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Pos\Models\PosProduct;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStock extends Model
{
    use HasUuid;

    protected $table = 'inventory_stocks';

    protected $fillable = [
        'pos_product_id',
        'quantity',
        'alert_threshold',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'alert_threshold' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(PosProduct::class, 'pos_product_id');
    }
}
