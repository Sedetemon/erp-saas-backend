<?php

namespace App\Support\Enums;


enum PermissionType:string
{

    case VIEW='view';

    case CREATE='create';

    case UPDATE='update';

    case DELETE='delete';

    case EXPORT='export';

    case APPROVE='approve';


}
