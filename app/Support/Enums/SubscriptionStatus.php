<?php

namespace App\Support\Enums;


enum SubscriptionStatus:string
{

    case ACTIVE='active';

    case PENDING='pending';

    case CANCELLED='cancelled';

    case EXPIRED='expired';

    case SUSPENDED='suspended';


}
