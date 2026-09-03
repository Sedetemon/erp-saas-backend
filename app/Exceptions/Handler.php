<?php

namespace App\Exceptions;


use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;


class Handler extends ExceptionHandler
{

    public function register(): void
    {

        $this->renderable(function(
            ERPException $exception,
            $request
        ){

            if($request->expectsJson())
            {

                return response()->json([

                    'success'=>false,

                    'message'=>$exception->getMessage(),

                    'errors'=>$exception->context()

                ], $exception->getCode());

            }

        });

    }

}
