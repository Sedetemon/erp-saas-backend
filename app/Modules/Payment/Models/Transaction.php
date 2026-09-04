<?php

namespace App\Modules\Payment\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    use HasUuid;

    protected $connection = 'tenant';
    protected $table = 'payment_transactions';

    protected $fillable = [
        'tenant_id', 'entity_type', 'entity_id', 'provider',
        'provider_reference', 'amount', 'currency', 'status',
        'meta', 'paid_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
        'paid_at' => 'datetime',
    ];

    public function entity(): MorphTo
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => 'succeeded', 'paid_at' => now()]);
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'meta' => array_merge($this->meta ?? [], ['failure_reason' => $reason])
        ]);
    }
}
