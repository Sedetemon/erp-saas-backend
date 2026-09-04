<?php

declare(strict_types=1);

use App\Models\Landlord\Domain;
use App\Models\Landlord\Tenant;

return [
    'tenant_model' => Tenant::class,
    'id_generator' => Stancl\Tenancy\UUIDGenerator::class,

    'domain_model' => Domain::class,

    /**
     * Domaines qui servent l'application centrale (landlord), par
     * opposition aux domaines/sous-domaines des tenants.
     */
    'central_domains' => array_filter(array_map('trim', explode(
        ',',
        env('CENTRAL_DOMAINS', 'erp-saas-backend.test,localhost,127.0.0.1')
    ))),

    /**
     * Tenancy bootstrappers exécutés à l'initialisation de la tenancy.
     */
    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
    ],

    /**
     * Config DB tenancy. Utilisée par DatabaseTenancyBootstrapper.
     */
    'database' => [
        // Connexion "landlord" définie dans config/database.php : la base
        // centrale qui contient tenants, plans, subscriptions, etc.
        'central_connection' => 'landlord',

        // "tenant_template" (config/database.php) sert de gabarit pour la
        // connexion générée dynamiquement à chaque tenant. Ne JAMAIS mettre
        // "tenant" ici : ce nom est réservé/recréé dynamiquement par le
        // package lui-même (voir le commentaire dans config/database.php).
        'template_tenant_connection' => 'tenant_template',

        /**
         * Les noms de bases de données tenant sont générés ainsi :
         * prefix + tenant_id + suffix.
         */
        'prefix' => 'erp_tenant_',
        'suffix' => '',

        'managers' => [
            'sqlite' => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
            'mysql' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'mariadb' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
        ],
    ],

    /**
     * Config cache tenancy. Utilisée par CacheTenancyBootstrapper.
     */
    'cache' => [
        'tag_base' => 'tenant',
    ],

    /**
     * Config filesystem tenancy. Utilisée par FilesystemTenancyBootstrapper.
     */
    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
        ],
        'root_override' => [
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],
        'suffix_storage_path' => true,
        'asset_helper_tenancy' => true,
    ],

    'redis' => [
        'prefix_base' => 'tenant',
        'prefixed_connections' => [
            // 'default',
        ],
    ],

    'features' => [
        // Stancl\Tenancy\Features\UserImpersonation::class,
        // Stancl\Tenancy\Features\UniversalRoutes::class,
    ],

    'routes' => true,

    /**
     * Paramètres utilisés par la commande tenants:migrate.
     */
    'migration_parameters' => [
        '--force' => true,
        '--path' => [
            database_path('migrations/tenant'),
            database_path('migrations/tenant/hotel'),
            database_path('migrations/tenant/inventory'),
            database_path('migrations/tenant/hr'),   // <-- à ajouter
        ],
        '--realpath' => true,
    ],

    'seeder_parameters' => [
        '--class' => 'DatabaseSeeder',
    ],

];
