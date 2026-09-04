<?php

namespace App\Support\Enums;

enum SubscriptionStatus: string
{
    case TRIAL = 'trial';

    case ACTIVE = 'active';

    case EXPIRED = 'expired';

    case CANCELLED = 'cancelled';

    case SUSPENDED = 'suspended';

}
