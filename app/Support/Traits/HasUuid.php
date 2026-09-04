<?php

namespace App\Support\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model) {

            if (empty($model->getKey())) {
                $model->{$model->getKeyName()} =
                    (string) Str::uuid();
            }

        });
    }


    public function getIncrementing()
    {
        return false;
    }

    public function getKeyType()
    {
        return 'string';
    }
}
