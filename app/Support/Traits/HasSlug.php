<?php

namespace App\Support\Traits;


use Illuminate\Support\Str;


trait HasSlug
{

    protected static function bootHasSlug()
    {

        static::creating(function ($model){

            if(empty($model->slug)){

                $model->slug =
                    Str::slug($model->name);

            }

        });

    }

}
