<?php

namespace App\Support\Enums;


enum InvoiceStatus:string
{

    case DRAFT='draft';

    case SENT='sent';

    case PARTIAL='partial';

    case PAID='paid';

    case OVERDUE='overdue';

    case CANCELLED='cancelled';


}
