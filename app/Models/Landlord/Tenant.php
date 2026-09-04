<?php

namespace App\Models\Landlord;

use App\Support\Enums\TenantStatus;
use App\Support\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Stancl\Tenancy\Database\Concerns\GeneratesIds;
use Stancl\Tenancy\Database\Concerns\HasDataColumn;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Concerns\HasInternalKeys;
use Stancl\Tenancy\Database\Concerns\InvalidatesResolverCache;
use Stancl\Tenancy\Database\Concerns\TenantRun;
use Stancl\Tenancy\Events\CreatingTenant;
use Stancl\Tenancy\Events\DeletingTenant;
use Stancl\Tenancy\Events\SavingTenant;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;
use Stancl\Tenancy\Events\TenantSaved;
use Stancl\Tenancy\Events\TenantUpdated;
use Stancl\Tenancy\Events\UpdatingTenant;

class Tenant extends Model implements TenantWithDatabase
{
    use CentralConnection;
    use GeneratesIds;
    use HasAuditLog;
    use HasDatabase;
    use HasDataColumn;
    use HasDomains;
    use HasInternalKeys;
    use InvalidatesResolverCache;
    use SoftDeletes;
    use TenantRun;

    /*
    |--------------------------------------------------------------------------
    | Configuration Eloquent
    |--------------------------------------------------------------------------
    */

    protected $connection = 'landlord';

    protected $table = 'tenants';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'email',
        'phone',
        'plan_id',
        'status',
        'trial_ends_at',
        'tenancy_db_name',
    ];

    protected $casts = [
        'status' => TenantStatus::class,
        'trial_ends_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Événements Stancl
    |--------------------------------------------------------------------------
    */

    protected $dispatchesEvents = [
        'saving' => SavingTenant::class,
        'saved' => TenantSaved::class,
        'creating' => CreatingTenant::class,
        'created' => TenantCreated::class,
        'updating' => UpdatingTenant::class,
        'updated' => TenantUpdated::class,
        'deleting' => DeletingTenant::class,
        'deleted' => TenantDeleted::class,
    ];

    /*
    |--------------------------------------------------------------------------
    | Contrat TenantWithDatabase
    |--------------------------------------------------------------------------
    */

    public function getTenantKeyName(): string
    {
        return 'id';
    }

    public function getTenantKey(): mixed
    {
        return $this->getAttribute($this->getTenantKeyName());
    }

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
            'email',
            'phone',
            'plan_id',
            'status',
            'trial_ends_at',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relations Landlord
    |--------------------------------------------------------------------------
    */

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'tenant_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function tenantModules(): HasMany
    {
        return $this->hasMany(TenantModule::class, 'tenant_id');
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(
            Module::class,
            'tenant_modules',
            'tenant_id',
            'module_id'
        )
            ->withPivot(['is_active', 'activated_at', 'settings'])
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | État du Tenant
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->status === TenantStatus::ACTIVE;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', TenantStatus::ACTIVE);
    }

    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    /*
    |--------------------------------------------------------------------------
    | Modules
    |--------------------------------------------------------------------------
    */

    public function hasModule(string $moduleName): bool
    {
        return $this->modules()
            ->where('modules.name', $moduleName)
            ->wherePivot('is_active', true)
            ->exists();
    }

    public function activeModules(): BelongsToMany
    {
        return $this->modules()->wherePivot('is_active', true);
    }

    public function tenantModule(string $moduleName): ?TenantModule
    {
        return $this->tenantModules()
            ->whereHas(
                'module',
                fn (Builder $query) => $query->where('name', $moduleName)
            )
            ->first();
    }
}
