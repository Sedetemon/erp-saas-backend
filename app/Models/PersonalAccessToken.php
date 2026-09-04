<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $connection = 'tenant';

    // Forcer le nom de table standard de Sanctum dans la DB tenant
    protected $table = 'personal_access_tokens';
}
