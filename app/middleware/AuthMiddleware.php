<?php

namespace app\middleware;

use app\helper\IpAddressHelper;
use app\helper\JwtHelper;
use app\repositories\user\UserRepository;
use app\validation\user\AuthValidate;
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

        $ip = IpAddressHelper::getRequestIp($request);
        Log::debug('Request from IP: ' . $ip);
        if ($this->shouldSkipAuth($request)) {
            return $next($request);
        }
        $token = $this->extractToken($request);

        if (!$token) {
            return $this->unauthorizedResponse('Missing authorization token');
        }

        try {
            $decoded = $this->jwtHelper->decodeToken($token);
            // return new Response(200, Response::$HEADERS_JSON, json_encode($decoded->sub));
            $userRepository = new UserRepository();
            $user = $userRepository->findByID($decoded->sub);
            if (!$user) {
                return new Response(401, Response::$HEADERS_JSON, json_encode(['status' => false, 'message' => 'Cant find user with token ']));
            }
            $this->attachUserData($request, $decoded);
        } catch (ExpiredException $e) {
            return $this->unauthorizedResponse('Token expired', 401);
        } catch (SignatureInvalidException $e) {
            return $this->unauthorizedResponse('Invalid token signature', 401);
        } catch (\Throwable $e) {
            return $this->unauthorizedResponse('Invalid token', 401);
        }
        return $next($request);
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

    public static function unauthorizedResponse(string $message, int $code = 401): Response
    {
        return new Response($code, ['WWW-Authenticate' => 'Bearer'], json_encode(['message' => $message ?? 'Unauthorized: Invalid or missing token'], JSON_UNESCAPED_UNICODE));
    }
}
