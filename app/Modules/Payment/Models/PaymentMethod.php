<?php

namespace App\Modules\Payment\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasUuid;

    protected $connection = 'tenant';
    protected $table = 'payment_methods';

    protected $fillable = [
        'tenant_id', 'user_id', 'provider', 'provider_token',
        'last_four', 'brand', 'is_default', 'meta'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'meta' => 'array',
    ];
}
