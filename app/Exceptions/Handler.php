<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{

    public function render($request, Throwable $e){
        if ($e instanceof AuthenticationException) {
            return response()->json(
                [
                    'type'      => 'error',
                    'status'    => Response::HTTP_UNAUTHORIZED,
                    'message'   => 'Unauthorised Access',
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return response()->json( 
                [
                    'type'      => 'error',
                    'success'   => Response::HTTP_METHOD_NOT_ALLOWED,
                    'message'   => 'Method is not allowed for the request.',
                ],
                Response::HTTP_METHOD_NOT_ALLOWED 
            );            
        }
        return parent::render($request, $e);
    }

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
