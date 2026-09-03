<?php

namespace App\Exceptions;

use Exception;

class ERPException extends Exception
{

    protected array $context;


    public function __construct(
        string $message,
        int $code = 400,
        array $context = []
    )
    {
        parent::__construct(
            $message,
            $code
        );

        $this->context = $context;
    }


    public function context(): array
    {
        return $this->context;
    }

}
