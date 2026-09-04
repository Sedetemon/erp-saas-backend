<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->bootMigrations();
        $this->bootEvents();
    }

    protected function bootMigrations(): void
    {
        $this->loadMigrationsFrom([
            database_path('migrations/landlord/Systeme'),
            database_path('migrations/landlord/tenancy'),
            database_path('migrations/landlord/security'),
            database_path('migrations/landlord/monitoring'),
        ]);
    }

    protected function bootEvents(): void
    {
        $events = [

            Events\TenantCreated::class => [
                JobPipeline::make([
                    Jobs\CreateDatabase::class,
                    Jobs\MigrateDatabase::class,
                ])->send(function (Events\TenantCreated $event) {
                    return $event->tenant;
                })->shouldBeQueued(false),
            ],

            Events\TenantDeleted::class => [
                JobPipeline::make([
                    Jobs\DeleteDatabase::class,
                ])->send(function (Events\TenantDeleted $event) {
                    return $event->tenant;
                })->shouldBeQueued(false),
            ],

            Events\TenancyInitialized::class => [
                Listeners\BootstrapTenancy::class,
            ],

            Events\TenancyEnded::class => [
                Listeners\RevertToCentralContext::class,
            ],

            Events\SyncedResourceSaved::class => [
                Listeners\UpdateSyncedResource::class,
            ],
        ];

        foreach ($events as $event => $listeners) {
            foreach ($listeners as $listener) {

                if ($listener instanceof JobPipeline) {
                    $listener = $listener->toListener();
                }

                Event::listen($event, $listener);
            }
        }
    }
}
