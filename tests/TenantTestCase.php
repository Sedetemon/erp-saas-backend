<?php

namespace Tests;

use App\Models\Landlord\Module;
use App\Models\Landlord\Tenant;
use App\Support\Enums\TenantStatus;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

abstract class TenantTestCase extends TestCase
{
    protected static bool $setupDone = false;

    protected Tenant $tenant;

    /**
     * À surcharger dans chaque TestCase de module.
     * Ex: protected static array $modulesToActivate = ['hr'];
     */
    protected static array $modulesToActivate = ['hotel'];

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection('tenant')->disableQueryLog();

        if (! static::$setupDone) {
            Artisan::call('migrate:fresh', [
                '--database' => 'landlord',
                '--force'    => true,
            ]);

            DB::connection('landlord')->statement(
                'DROP DATABASE IF EXISTS `erp_tenant_testing`'
            );

            static::$setupDone = true;
        }

        $this->tenant = Tenant::on('landlord')->firstOrCreate(
            ['id' => 'testing'],
            [
                'name'   => 'Testing Tenant',
                'slug'   => 'testing-tenant',
                'email'  => 'tenant-testing@example.com',
                'status' => TenantStatus::ACTIVE,
            ]
        );

        tenancy()->initialize($this->tenant);

        foreach (static::$modulesToActivate as $moduleName) {
            $module = Module::on('landlord')->where('name', $moduleName)->firstOrFail();

            $this->tenant->modules()->syncWithoutDetaching([
                $module->id => [
                    'is_active'    => true,
                    'activated_at' => now(),
                ],
            ]);
        }

        Artisan::call('tenants:migrate', [
            '--tenants' => [$this->tenant->id],
            '--force'   => true,
        ]);

        DB::connection('landlord')->beginTransaction();
        DB::connection('tenant')->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (DB::connection('tenant')->transactionLevel() > 0) {
            DB::connection('tenant')->rollBack();
        }

        if (DB::connection('landlord')->transactionLevel() > 0) {
            DB::connection('landlord')->rollBack();
        }

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }
}