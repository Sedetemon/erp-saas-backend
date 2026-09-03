<?php

namespace App\Support\Traits;


trait HasFilters
{

    public function scopeFilter(
        $query,
        array $filters
    )
    {

        foreach($filters as $field=>$value)
        {

            if(
                !empty($value)
            ){

                $query->where(
                    $field,
                    'like',
                    "%$value%"
                );

            }

        }


        return $query;

    }

}
