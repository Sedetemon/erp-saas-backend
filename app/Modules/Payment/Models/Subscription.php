<?php

namespace App\Modules\Payment\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Subscription extends Model
{
    use HasUuid;

    protected $connection = 'tenant';
    protected $table = 'payment_subscriptions';

    protected $fillable = [
        'tenant_id', 'entity_type', 'entity_id', 'plan_name',
        'amount', 'currency', 'interval', 'starts_at',
        'ends_at', 'status', 'meta'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'meta' => 'array',
    ];

    public function entity(): MorphTo
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}
