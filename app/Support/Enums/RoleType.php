<?php

namespace App\Support\Enums;


enum RoleType:string
{

    case SUPER_ADMIN='super_admin';

    case TENANT_ADMIN='tenant_admin';

    case MANAGER='manager';

    case USER='user';

    case EMPLOYEE='employee';


}
