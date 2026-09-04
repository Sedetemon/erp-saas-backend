<?php

namespace App\Support\Enums;

enum TenantStatus: string
{
    case PENDING = 'pending';

    case TRIAL = 'trial';

    case ACTIVE = 'active';

    case SUSPENDED = 'suspended';

    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',

            self::TRIAL => 'Période d’essai',

            self::ACTIVE => 'Actif',

            self::SUSPENDED => 'Suspendu',

            self::EXPIRED => 'Expiré',
        };
    }
}
