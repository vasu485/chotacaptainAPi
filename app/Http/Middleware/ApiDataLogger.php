<?php

namespace App\Http\Middleware;
use Closure;
use JWTAuth;
use Auth;
use Exception;
use Tymon\JWTAuthExceptions\JWTException;
date_default_timezone_set('Asia/Kolkata');
class ApiDataLogger
{
    private $startTime;
    /**
    * Handle an incoming request.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  \Closure  $next
    * @return mixed
    */

    public function handle($request, Closure $next)
    {
        $this->startTime = microtime(true);
        return $next($request);
    }

    public function terminate($request, $response)
    {
         if ( env('API_DATALOGGER', true) ) 
         {
               $endTime = microtime(true);
               $filename = 'api_datalogger_' . date('d-m-y') . '.log';
               $dataToLog  = 'Time: '   . date("F j, Y, g:i a") . "\n";
               $dataToLog .= 'Duration: ' . number_format($endTime - LARAVEL_START, 3) . "\n";
               $dataToLog .= 'IP Address: ' . $request->ip() . "\n";
               $dataToLog .= 'URL: '    . $request->fullUrl() . "\n";
               $dataToLog .= 'Method: ' . $request->method() . "\n";
               $dataToLog .= 'Headers: ' . $request->header('Authorization') . "\n";

               if($request->header('Authorization')!=null && $request->header('Authorization')!=''){
                    //$dataToLog.= 'User: ' .Auth::guard('api')->user(). "\n";
                    $dataToLog.= 'User: ' .Auth::guard('api')->id(). "\n";
               }
               
               $dataToLog .= 'Input: '  . $request->getContent() . "\n";
               $dataToLog .= 'Output: ' . $response->getContent() . "\n";
              \File::append( storage_path('logs' . DIRECTORY_SEPARATOR . $filename), $dataToLog . "\n" . str_repeat("=", 20) . "\n\n");
          }
    }

}
