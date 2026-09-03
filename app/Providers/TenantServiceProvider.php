<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {

        $this->app->singleton(

            'tenant',

            function () {

                return null;

            }

        );

    }

    public function boot(): void {}
}
