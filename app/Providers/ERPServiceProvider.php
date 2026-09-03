<?php

namespace App\Providers;

use App\Services\ERP\ERPManager;
use Illuminate\Support\ServiceProvider;

class ERPServiceProvider extends ServiceProvider
{
    public function register(): void
    {

        /*
        |--------------------------------------------------------------------------
        | Services ERP
        |--------------------------------------------------------------------------
        */

        $this->app->singleton(
            ERPManager::class,
            function () {

                return new ERPManager;

            }
        );

    }

    public function boot(): void
    {

        //

    }
}
