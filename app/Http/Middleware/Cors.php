<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Response;

class Cors
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

//        $host = substr($_SERVER['SERVER_NAME'], strlen('api.'));
//        $accessControlAllowOrigin = 'https://' . $host ;
//        if (!is_null(env('API_CORS_PORT_ALLOW'))) {
//            $accessControlAllowOrigin .= (':' . env('API_CORS_PORT_ALLOW'));
//        }

        $headers = [
            'Access-Control-Allow-Origin'      => env('APP_URL_FRONT'),
            // CORS doesn't accept Access-Control-Allow-Origin = * for security reasons
            //'Access-Control-Allow-Origin'    => '*',
            //'Access-Control-Allow-Methods'     => 'POST, OPTIONS',
            'Access-Control-Allow-Methods'   => 'POST, GET, OPTIONS, PUT, DELETE',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'           => '86400',
            //'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With',
            'Access-Control-Allow-Headers'   => 'X-Custom-Header, X-Requested-With, Content-Type, Origin, Authorization, Accept, Client-security-token',
        ];


        //Using this you don't need an method for 'OPTIONS' on controller
        if ($request->isMethod('OPTIONS'))
            return Response::json('{"method":"OPTIONS"}', 200, $headers);

        // For all other cases
        $response = $next($request);
        foreach ($headers as $key => $value)
            $response->header($key, $value);

        return $response;

    }
}


