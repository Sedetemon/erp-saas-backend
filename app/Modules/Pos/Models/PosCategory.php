<?php

namespace App\Modules\Pos\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosCategory extends Model
{
    use HasUuid;

    protected $table = 'pos_categories';

    protected $fillable = ['name', 'sort_order', 'is_active'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(PosProduct::class);
    }
}
