<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
       
        if ($exception instanceof UnauthorizedHttpException) 
        {
            $preException = $exception->getPrevious();
            if ($preException instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) 
            {
                return response()->json(['status' => 'error','message' => 'token_expired','data' => null],400);
            } else if ($preException instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) 
            {
                return response()->json(['status' => 'error','message' => 'token_invalid','data' => null],400);
            } else if ($preException instanceof \Tymon\JWTAuth\Exceptions\TokenBlacklistedException) 
            {
                 return response()->json(['status' => 'error','message' => 'token_blacklisted','data' => null],400);
            }
            if ($exception->getMessage() === 'Token not provided') {
               return response()->json(['status' => 'error','message' => 'token_not_provided','data' => null],400);
            }
        }

        $exception = $this->prepareException($exception);

        if ($exception instanceof \Illuminate\Http\Exception\HttpResponseException) {
            return $exception->getResponse();
        }
        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            return $this->unauthenticated($request, $exception);
        }
        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return $this->convertValidationExceptionToResponse($exception, $request);
        }

        $response = [];

        $statusCode = 500;
        if (method_exists($exception, 'getStatusCode')) {
            $statusCode = $exception->getStatusCode();
        }

        switch ($statusCode) {
            case 404:
                $message = 'Not Found';
                break;

            case 403:
                $message = 'Forbidden';
                break;

            case 405:
                $message = 'Method Not Allowed';
                break;

            /*case 500:
                $message = 'Something went wrong, please try again later';
                break;*/

            default:
                $message = $exception->getMessage();
                break;
        }

        if (config('app.debug')) {
            $response['status'] = 'error';
            $response['message'] = $message;
            $response['data'] = null;
        }

        return response()->json($response, $statusCode);
        // return parent::render($request, $exception);
    }

}

