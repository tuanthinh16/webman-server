<?php

namespace app\middleware;

use app\helper\IpAddressHelper;
use app\helper\JwtHelper;
use Webman\MiddlewareInterface;
use Webman\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use support\Log;
use support\Response;

class AuthMiddleware implements MiddlewareInterface
{
    private $jwtHelper;

    public function __construct()
    {
        $this->jwtHelper = new JwtHelper();
    }

    public function process(Request $request, callable $next): Response
    {

        try {
            $ip = IpAddressHelper::getRequestIp($request);
            Log::debug('Request from IP: ' . $ip);
            if ($this->shouldSkipAuth($request)) {
                return $next($request);
            }
            $token = $this->extractToken($request);
            if (!$token) {
                return Response::UnAuthorize('Missing authorization token');
            }

            try {
                $decoded = $this->jwtHelper->decodeToken($token);
                $this->attachUserData($request, $decoded);
            } catch (ExpiredException $e) {
                return Response::UnAuthorize('Token expired');
            } catch (SignatureInvalidException $e) {
                return Response::UnAuthorize('Invalid token signature');
            } catch (\Throwable $e) {
                return Response::UnAuthorize('Invalid token');
            }
            return $next($request);
        } catch (\Throwable $e) {
            Log::error('AuthMiddleware@Proccess error: ' . $e->getMessage());
            return Response::ServerError();
        }
    }
    protected function shouldSkipAuth(Request $request): bool
    {
        $publicRoutes = [
            '/api/v1/users/search',
            '/'
        ];

        return in_array($request->path(), $publicRoutes);
    }
    protected function extractToken(Request $request): ?string
    {
        $authHeader = $request->header('Authorization', '');
        if (preg_match('/^Bearer\s+(\S+)$/', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }


    protected function attachUserData(Request $request, object $decoded): void
    {
        $request->user = [
            'id' => $decoded->sub ?? null,
            'role' => $decoded->role ?? null,
            'exp' => $decoded->exp ?? null
        ];
    }
}
