<?php

namespace App\Exceptions;


class BusinessException extends ERPException
{

    public static function error(
        string $message
    ): self
    {
        return new self(
            $message,
            422
        );
    }

}
