<?php

namespace App\Support\Enums;

enum TenantStatus: string
{
    case ACTIVE = 'active';

    case INACTIVE = 'inactive';

    case SUSPENDED = 'suspended';

    case TRIAL = 'trial';

    case EXPIRED = 'expired';


    public function label(): string
    {
        return match($this)
        {
            self::ACTIVE => 'Actif',

            self::INACTIVE => 'Inactif',

            self::SUSPENDED => 'Suspendu',

            self::TRIAL => 'Période d’essai',

            self::EXPIRED => 'Expiré',
        };
    }
}
