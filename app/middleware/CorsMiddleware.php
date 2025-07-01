<?php

namespace app\middleware;

use support\Log;
use Webman\MiddlewareInterface;
use Webman\Http\Request;
use Webman\Http\Response;

class CorsMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        if (strtoupper($request->method()) === 'OPTIONS') {
            return new Response(
                '',
                204,
                [
                    'Access-Control-Allow-Origin'      => '*',
                    'Access-Control-Allow-Methods'     => 'GET,POST,PUT,DELETE,OPTIONS,PATCH',
                    'Access-Control-Allow-Headers'     => 'Authorization,Content-Type,Accept',
                    'Access-Control-Allow-Credentials' => 'true',
                ]
            );
        }

        // 2) Otherwise let the request run, then add CORS headers on the Response
        $response = $next($request);
        $response = $response->withHeaders([
            'Access-Control-Allow-Origin'      => '*',
            'Access-Control-Allow-Methods'     => 'GET,POST,PUT,DELETE,OPTIONS,PATCH',
            'Access-Control-Allow-Headers'     => 'Authorization,Content-Type,Accept',
            'Access-Control-Allow-Credentials' => 'true',
        ]);
        return $response;
    }
}
