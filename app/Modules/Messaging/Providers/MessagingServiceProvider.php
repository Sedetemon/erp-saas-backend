<?php

namespace App\Modules\Messaging\Providers;

use Illuminate\Support\ServiceProvider;

class MessagingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Enregistrer le service dans le conteneur
        $this->app->singleton(
            \App\Modules\Messaging\Services\MessagingService::class
        );
    }

    public function boot(): void
    {
        // Charger les migrations du module situées dans le dossier database/migrations du module
        // $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Charger les routes du module (si tu veux les séparer)
        // $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
