<?php

namespace App\Models\Landlord;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Contracts\Domain as DomainContract;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Stancl\Tenancy\Database\Concerns\ConvertsDomainsToLowercase;
use Stancl\Tenancy\Database\Concerns\EnsuresDomainIsNotOccupied;
use Stancl\Tenancy\Database\Concerns\InvalidatesTenantsResolverCache;

class Domain extends Model implements DomainContract
{
    use CentralConnection;
    use ConvertsDomainsToLowercase;
    use EnsuresDomainIsNotOccupied;
    use HasUuid;
    use InvalidatesTenantsResolverCache;

    protected $table = 'domains';

    protected $fillable = [
        'tenant_id',
        'domain',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
