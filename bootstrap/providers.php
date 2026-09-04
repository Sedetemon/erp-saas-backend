<?php

use App\Providers\AppServiceProvider;
use App\Providers\ERPServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\ModuleServiceProvider;
use App\Providers\RepositoryServiceProvider;
use App\Providers\TenancyServiceProvider;

return [
    AppServiceProvider::class,

    EventServiceProvider::class,

    TenancyServiceProvider::class,

    ERPServiceProvider::class,

    ModuleServiceProvider::class,

    RepositoryServiceProvider::class,

    App\Modules\Payment\Providers\PaymentServiceProvider::class,
    App\Modules\Messaging\Providers\MessagingServiceProvider::class, // <-- Ajoutez cette ligne
];
