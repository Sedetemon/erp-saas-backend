<?php

namespace App\Exceptions;


class PermissionException extends ERPException
{

    public static function denied(): self
    {
        return new self(
            'Permission refusée',
            403
        );
    }

}
