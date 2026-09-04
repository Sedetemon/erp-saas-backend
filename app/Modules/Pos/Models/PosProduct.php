<?php
// app/Modules/Pos/Models/PosProduct.php

namespace App\Modules\Pos\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class PosProduct extends Model
{
    use HasUuid, SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'pos_products';

    protected $fillable = [
        'id',
        'pos_category_id',
        'name',
        'sku',
        'price',
        'cost_price',
        'stock',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
    ];

    // ✅ AJOUTER LA RELATION category
    public function category()
    {
        return $this->belongsTo(PosCategory::class, 'pos_category_id');
    }

    // ✅ ALIAS category pour les tests (si le test attend 'category')
    public function getCategoryAttribute()
    {
        return $this->category()->first();
    }

    // Scopes...
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
