<?php

namespace App\Models\Landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantModule extends Model
{
    protected $connection = 'landlord';

    protected $table = 'tenant_modules';

    protected $fillable = [
        'tenant_id',
        'module_id',
        'is_active',
        'activated_at',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'activated_at' => 'datetime',
        'settings' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id');
    }
}
