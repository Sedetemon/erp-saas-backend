<?php

use App\Providers\AppServiceProvider;
use App\Providers\ERPServiceProvider;
use App\Providers\ModuleServiceProvider;
use App\Providers\RepositoryServiceProvider;
use App\Providers\TenantServiceProvider;

return [
    AppServiceProvider::class,

    ERPServiceProvider::class,

    TenantServiceProvider::class,

    ModuleServiceProvider::class,

    RepositoryServiceProvider::class,

];
