<?php

namespace App\Services\Platform;

use App\Models\Landlord\Module;
use App\Models\Landlord\Tenant;
use App\Models\Landlord\TenantModule;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TenantProvisioningService
{
    /**
     * Crée complètement un tenant avec sa base, ses modules et son administrateur initial.
     *
     * @return array{tenant: Tenant, admin_password: string, admin_user: TenantUser}
     */
    public function provision(array $data, array $moduleNames = []): array
    {
        $centralConnection = config('tenancy.database.central_connection', 'landlord');

        return DB::connection($centralConnection)->transaction(function () use ($data, $moduleNames) {

            // 1. Mot de passe temporaire fourni ou généré dynamiquement
            $temporaryPassword = $data['admin_password'] ?? Str::password(length: 12, symbols: true);

            // 2. Création du Tenant (Déclenche Jobs\CreateDatabase de Stancl)
            $tenant = Tenant::create([
                'name'          => $data['name'],
                'slug'          => $data['slug'],
                'email'         => $data['email'] ?? null,
                'phone'         => $data['phone'] ?? null,
                'plan_id'       => $data['plan_id'] ?? null,
                'status'        => $data['status'] ?? 'trial',
                'trial_ends_at' => $data['trial_ends_at'] ?? null,
            ]);

            try {
                $adminUser = null;

                // 3. Exécution dans le contexte BDD du Tenant
                $tenant->run(function () use ($data, $moduleNames, $temporaryPassword, &$adminUser) {

                    // A. Migrations Core
                    $coreMigrationRelativePath = 'database/migrations/tenant/core';
                    if (!is_dir(base_path($coreMigrationRelativePath))) {
                        throw new RuntimeException("Dossier des migrations socle [{$coreMigrationRelativePath}] introuvable.");
                    }
                    $this->runTenantMigrations($coreMigrationRelativePath);

                    // B. Migrations Modules
                    foreach ($moduleNames as $moduleName) {
                        $this->migrateModuleTables($moduleName);
                    }

                    // C. Création du Premier Utilisateur Admin dans la BDD Client
                    $adminUser = TenantUser::create([
                        'name'              => $data['admin_name'] ?? 'Administrateur',
                        'email'             => $data['email'],
                        'password'          => Hash::make($temporaryPassword),
                        'is_admin'          => true,
                        'email_verified_at' => now(),
                    ]);
                });

                // 4. Enregistrement des relations de modules dans la base Landlord
                foreach ($moduleNames as $moduleName) {
                    $this->registerModuleInLandlord($tenant, $moduleName);
                }

                return [
                    'tenant'         => $tenant->fresh(),
                    'admin_user'     => $adminUser,
                    'admin_password' => $temporaryPassword, // Renvoyé uniquement au provisioning (pour envoi d'email)
                ];

            } catch (Throwable $e) {
                Log::error('Erreur lors du provisioning du tenant.', [
                    'tenant_id' => $tenant->id ?? null,
                    'slug'      => $data['slug'] ?? null,
                    'error'     => $e->getMessage(),
                ]);

                // Suppression de la BDD physique orpheline en cas d'échec
                try {
                    $tenant->database()->delete();
                } catch (Throwable $cleanupException) {
                    Log::warning('Impossible de supprimer la BDD physique après échec.', [
                        'tenant_id' => $tenant->id ?? null,
                        'error'     => $cleanupException->getMessage(),
                    ]);
                }

                throw $e;
            }
        });
    }

    protected function runTenantMigrations(string $relativePath): void
    {
        $exitCode = Artisan::call('migrate', [
            '--path'  => $relativePath,
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException("Échec des migrations pour le chemin : {$relativePath}");
        }
    }

    protected function migrateModuleTables(string $moduleName): void
    {
        $moduleMigrationRelativePath = "database/migrations/tenant/{$moduleName}";

        if (is_dir(base_path($moduleMigrationRelativePath))) {
            $this->runTenantMigrations($moduleMigrationRelativePath);
        }
    }

    protected function registerModuleInLandlord(Tenant $tenant, string $moduleName): void
    {
        /** @var Module|null $module */
        $module = Module::query()
            ->where('name', '=', $moduleName)
            ->where('is_active', '=', true)
            ->first();

        if (!$module) {
            throw new RuntimeException("Le module [{$moduleName}] n'existe pas ou est désactivé.");
        }

        TenantModule::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'module_id' => $module->id,
            ],
            [
                'is_active'    => true,
                'activated_at' => now(),
                'settings'     => null,
            ]
        );
    }
}
