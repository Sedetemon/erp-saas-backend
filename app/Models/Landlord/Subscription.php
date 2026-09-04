<?php

namespace App\Models\Landlord;

use App\Support\Enums\SubscriptionStatus;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasUuid;

    protected $connection = 'landlord';

    protected $table = 'subscriptions';

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
        'status',
        'auto_renew',
        'amount',
        'currency',
        'payment_provider',
        'payment_reference',
    ];

    protected $casts = [
        'status' => SubscriptionStatus::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'auto_renew' => 'boolean',
        'amount' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
}
