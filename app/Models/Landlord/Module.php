<?php

namespace App\Models\Landlord;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    use HasUuid;

    protected $connection = 'landlord';

    protected $table = 'modules';

    protected $fillable = [
        'name',
        'slug',  // ⚠️ Doit être présent
        'label',
        'description',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenantModules(): HasMany
    {
        return $this->hasMany(TenantModule::class);
    }
}
