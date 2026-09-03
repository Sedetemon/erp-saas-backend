<?php

namespace App\Support\Enums;


enum PaymentStatus:string
{

    case PENDING='pending';

    case PAID='paid';

    case FAILED='failed';

    case REFUNDED='refunded';

    case CANCELLED='cancelled';


}
