<?php

namespace App\Exceptions;


class ValidationException extends ERPException
{

    public static function failed(
        array $errors
    ): self
    {
        return new self(
            'Erreur de validation',
            422,
            $errors
        );
    }

}
