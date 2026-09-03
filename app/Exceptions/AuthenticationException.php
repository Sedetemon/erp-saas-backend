<?php

namespace App\Exceptions;


class AuthenticationException extends ERPException
{

    public static function invalid(): self
    {
        return new self(
            'Identifiants invalides',
            401
        );
    }


    public static function expired(): self
    {
        return new self(
            'Session expirée',
            401
        );
    }

}
