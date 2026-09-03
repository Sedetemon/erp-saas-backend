<?php

namespace App\Support\Traits;


use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


trait HasAuditLog
{

    use LogsActivity;


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

}
