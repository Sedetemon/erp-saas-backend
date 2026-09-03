<?php

namespace App\Exceptions;


class TenantException extends ERPException
{

    public static function notFound(): self
    {
        return new self(
            'Tenant introuvable',
            404
        );
    }


    public static function inactive(): self
    {
        return new self(
            'Tenant inactif ou suspendu',
            403
        );
    }


}
