<?php

namespace App\Modules\Inventory\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(string $itemName, float $available, float $requested)
    {
        parent::__construct(
            "Stock insuffisant pour \"{$itemName}\" : disponible {$available}, demandé {$requested}."
        );
    }
}
