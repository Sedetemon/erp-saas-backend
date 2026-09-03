<?php

namespace App\Models\Landlord;


use App\Support\Enums\TenantStatus;
use App\Support\Traits\HasUuid;
use App\Support\Traits\HasAuditLog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Tenant extends Model
{

    use HasUuid;
    use HasAuditLog;


    protected $connection = 'landlord';


    protected $table = 'tenants';


    protected $fillable = [

        'name',

        'slug',

        'database',

        'status',

        'settings',

    ];



    protected $casts = [

        'status'=>TenantStatus::class,

        'settings'=>'array',

    ];



    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */


    public function domains(): HasMany
    {
        return $this->hasMany(
            Domain::class
        );
    }



    public function modules(): HasMany
    {
        return $this->hasMany(
            TenantModule::class
        );
    }



    public function subscriptions(): HasMany
    {
        return $this->hasMany(
            Subscription::class
        );
    }



    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */


    public function isActive(): bool
    {
        return $this->status
            === TenantStatus::ACTIVE;
    }



    public function databaseName(): string
    {
        return $this->database;
    }


}
