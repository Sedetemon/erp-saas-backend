<?php

namespace App\Providers;

use App\Models\TenantPersonalAccessToken;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Force Sanctum à utiliser la table personal_access_tokens sur la connexion active (tenant)
        Sanctum::usePersonalAccessTokenModel(TenantPersonalAccessToken::class);
    }
}
