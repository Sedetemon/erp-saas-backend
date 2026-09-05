<?php

namespace App\Modules\Inventory\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasUuid, SoftDeletes;

    protected $table = 'inventory_items';

    protected $fillable = [
        'name',
        'sku',
        'unit',
        'category',
        'is_active',
        'itemable_type',
        'itemable_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function stock(): HasOne
    {
        return $this->hasOne(InventoryStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Entité source optionnelle (ex: PosProduct) dont cet item de stock
     * dérive, si applicable.
     */
    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
