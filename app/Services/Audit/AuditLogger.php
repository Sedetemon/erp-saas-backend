<?php

namespace App\Services\Audit;

use App\Models\Landlord\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

/**
 * Enregistre un événement de sécurité/conformité dans audit_logs.
 *
 * Usage :
 *   app(AuditLogger::class)->log(
 *       event: 'login',
 *       causer: $user,
 *       logName: 'authentification',
 *   );
 */
class AuditLogger
{
    public function log(
        string $event,
        ?Model $causer = null,
        ?Model $auditable = null,
        string $logName = 'default',
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $tenantId = null,
    ): AuditLog {
        return AuditLog::create([
            'tenant_id' => $tenantId ?? (tenancy()->initialized ? tenant('id') : null),
            'causer_type' => $causer?->getMorphClass(),
            'causer_id' => $causer?->getKey(),
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'event' => $event,
            'log_name' => $logName,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => Request::fullUrl(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
