<?php

namespace Tests;

use App\Models\Landlord\Module;
use App\Models\Landlord\Tenant;
use App\Support\Enums\TenantStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;  // ⚠️ NOUVELLE LIGNE

abstract class TenantTestCase extends TestCase
{
    use DatabaseTransactions;

    protected static bool $setupDone = false;
    protected Tenant $tenant;

    protected static array $modulesToActivate = ['hr'];

    protected function setUp(): void
    {
        parent::setUp();  // ⚠️ DOIT ÊTRE EN PREMIER !
DB::connection('landlord')->getSchemaBuilder()->create('activity_log', function ($table) {
    $table->increments('id');
    $table->string('log_name')->nullable();
    $table->text('description')->nullable();
    $table->nullableMorphs('subject');
    $table->nullableMorphs('causer');
    $table->json('properties')->nullable();
    $table->uuid('batch_uuid')->nullable();
    $table->timestamps();
});

        // Configuration SQLite APRES parent::setUp()
        config(['activitylog.enabled' => false]);
        config(['database.default' => 'sqlite']);
        config(['database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]]);
        config(['database.connections.tenant' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]]);

        // Exécuter les migrations landlord
        Artisan::call('migrate:fresh', [
            '--database' => 'landlord',
            '--force' => true,
            '--path' => 'database/migrations/landlord/tenancy'
        ]);

        // Créer un module HR pour les tests
        $hrModule = Module::on('landlord')->firstOrCreate(
            ['name' => 'hr'],
            [
                'slug' => 'hr',
                'label' => 'Ressources Humaines',
                'is_active' => true
            ]
        );

        // Créer un tenant de test
        $this->tenant = Tenant::on('landlord')->firstOrCreate(
            ['slug' => 'test-tenant'],
            [
                'name' => 'Test Tenant',
                'email' => 'test-tenant@example.com',
                'database' => ':memory:',
                'status' => TenantStatus::ACTIVE,
            ]
        );

        // Activer le module HR pour ce tenant
        $this->tenant->modules()->syncWithoutDetaching([
            $hrModule->id => ['is_active' => true, 'activated_at' => now()]
        ]);

        // Initialiser le tenant
        tenancy()->initialize($this->tenant);

        // Créer les tables tenant
        Artisan::call('migrate:fresh', [
            '--database' => 'tenant',
            '--force' => true,
            '--path' => 'database/migrations/tenant/hr'
        ]);
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        parent::tearDown();
    }
}
