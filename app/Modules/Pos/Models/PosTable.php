<?php

namespace App\Modules\Pos\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosTable extends Model
{
    use HasUuid;

    protected $table = 'pos_tables';

    protected $fillable = ['name', 'area', 'status'];

    public function orders(): HasMany
    {
        return $this->hasMany(PosOrder::class);
    }
}
