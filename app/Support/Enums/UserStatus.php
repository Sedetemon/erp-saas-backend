<?php

namespace App\Support\Enums;


enum UserStatus:string
{

    case ACTIVE='active';

    case INACTIVE='inactive';

    case BLOCKED='blocked';

    case PENDING='pending';


}
