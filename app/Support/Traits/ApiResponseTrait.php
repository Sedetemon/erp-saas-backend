<?php

namespace App\Support\Traits;


trait ApiResponseTrait
{

    protected function success(
        mixed $data = null,
        string $message = 'Success'
    )
    {

        return response()->json([

            'success'=>true,

            'message'=>$message,

            'data'=>$data

        ]);

    }



    protected function error(
        string $message,
        int $code=400
    )
    {

        return response()->json([

            'success'=>false,

            'message'=>$message

        ],$code);

    }

}
