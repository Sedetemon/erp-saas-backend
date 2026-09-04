<?php

namespace App\Models\Landlord;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Journal de sécurité / conformité au niveau SaaS (connexions, changements
 * de rôle, suspension d'un tenant, exports, impersonation...).
 *
 * À distinguer de spatie/activitylog (`activity_log`, trait HasAuditLog) qui
 * trace les changements de champs sur un modèle Eloquent précis.
 */
class AuditLog extends Model
{
    use HasUuid;

    protected $connection = 'landlord';

    protected $table = 'audit_logs';

    public $timestamps = true;

    protected $fillable = [
        'tenant_id',
        'causer_type',
        'causer_id',
        'auditable_type',
        'auditable_id',
        'event',
        'log_name',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
        'batch_uuid',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
