<?php

namespace App\Models\Landlord;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasUuid;

    protected $connection = 'landlord';

    protected $table = 'plans';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'max_users',
        'max_storage',
        'is_active',
        'features',
        'is_public',
        'sort_order',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'max_users' => 'integer',
        'max_storage' => 'integer',
        'is_active' => 'boolean',
        'features' => 'array',
        'is_public' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'plan_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }
}
