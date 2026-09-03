<?php

namespace App\Support\Enums;


enum ModuleStatus: string
{

    case ENABLED = 'enabled';

    case DISABLED = 'disabled';

    case INSTALLING = 'installing';

    case UPDATING = 'updating';

    case ERROR = 'error';


}
