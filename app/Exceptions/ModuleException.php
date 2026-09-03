<?php

namespace App\Exceptions;


class ModuleException extends ERPException
{

    public static function disabled(
        string $module
    ): self
    {
        return new self(
            "Le module {$module} est désactivé",
            403
        );
    }


    public static function missing(
        string $module
    ): self
    {
        return new self(
            "Module {$module} introuvable",
            404
        );
    }

}
