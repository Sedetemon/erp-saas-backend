<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class TenantPersonalAccessToken extends SanctumPersonalAccessToken
{
    // Indique la connexion tenant
    protected $connection = 'tenant';

    // Force Eloquent à utiliser la table standard 'personal_access_tokens'
    protected $table = 'personal_access_tokens';
}
