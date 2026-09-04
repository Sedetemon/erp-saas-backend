<?php

namespace App\Modules\Payment\Providers;

use App\Modules\Payment\Services\PaymentService;
use App\Modules\Payment\Services\MobileMoneyService;
use App\Modules\Payment\Services\CardPaymentService;
use App\Modules\Payment\Services\WebhookService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Le nom du module (pour les chemins)
     */
    protected string $moduleName = 'Payment';
    protected string $moduleNameLower = 'payment';

    /**
     * Enregistrement des services dans le conteneur.
     */
    public function register(): void
    {
        // Enregistrer les services principaux en singleton
        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService(
                $app->make(MobileMoneyService::class),
                $app->make(CardPaymentService::class)
            );
        });

        $this->app->singleton(MobileMoneyService::class);
        $this->app->singleton(CardPaymentService::class);
        $this->app->singleton(WebhookService::class);

        // Enregistrer les événements
        $this->registerEvents();
    }

    /**
     * Boot du module (chargement des routes, migrations, etc.)
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerMigrations();
        $this->registerTranslations();
        $this->registerConfig();
    }

    /**
     * Charger les routes du module
     */
    protected function registerRoutes(): void
    {
        // Vérifier que le fichier de routes existe
        $routesPath = module_path($this->moduleName, 'Routes/api.php');
        if (file_exists($routesPath)) {
            Route::middleware(['api'])
                ->prefix('api')
                ->group($routesPath);
        }
    }

    /**
     * Charger les migrations du module
     */
    protected function registerMigrations(): void
    {
        $migrationsPath = module_path($this->moduleName, 'Database/Migrations');
        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    /**
     * Charger les traductions (si utilisées)
     */
    protected function registerTranslations(): void
    {
        $langPath = module_path($this->moduleName, 'Resources/lang');
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        }
    }

    /**
     * Publier la configuration (si besoin)
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->moduleName, 'Config/config.php');
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, $this->moduleNameLower);
        }
    }

    /**
     * Enregistrer les événements et listeners du module
     */
    protected function registerEvents(): void
    {
        // Écouter les événements de paiement pour les modules métier
        $this->app['events']->listen(
            \App\Modules\Payment\Events\PaymentSucceeded::class,
            \App\Modules\Payment\Listeners\HandlePaymentSucceeded::class
        );

        $this->app['events']->listen(
            \App\Modules\Payment\Events\PaymentFailed::class,
            \App\Modules\Payment\Listeners\HandlePaymentFailed::class
        );
    }
}

/**
 * Helper pour obtenir le chemin d'un module
 * (à placer dans un helper global ou dans ce fichier)
 */
if (!function_exists('module_path')) {
    function module_path(string $module, string $path = ''): string
    {
        return base_path("app/Modules/{$module}/" . ltrim($path, '/'));
    }
}
