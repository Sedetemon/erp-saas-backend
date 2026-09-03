<?php

namespace App\Support\Traits;

trait HasTenantConnection
{
    public function getConnectionName()
    {
        return 'tenant';
    }
}
