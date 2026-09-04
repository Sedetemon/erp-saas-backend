<?php

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    // ⚠️ Utiliser la connexion landlord (globale)
    protected $connection = 'landlord';
    protected $table = 'webhook_logs';

    protected $fillable = [
        'provider', 'event_type', 'payload', 'status'
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function markAsProcessed(): void
    {
        $this->update(['status' => 'processed']);
    }

    public function markAsFailed(string $reason = null): void
    {
        $payload = $this->payload ?? [];
        $payload['failure_reason'] = $reason ?? 'Unknown error';
        $this->update([
            'status' => 'failed',
            'payload' => $payload,
        ]);
    }
}
