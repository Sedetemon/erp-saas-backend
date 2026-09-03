<?php

namespace App\Exceptions;


class DatabaseException extends ERPException
{

    public static function connectionFailed(): self
    {
        return new self(
            'Impossible de se connecter à la base de données',
            500
        );
    }

}
