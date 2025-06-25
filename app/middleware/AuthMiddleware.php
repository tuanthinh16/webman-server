<?php

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use support\Log;
use Webman\Http\Response;

class AuthMiddleware implements MiddlewareInterface
{
    private static string $secret;
    private static string $algorithm = 'HS256';

    public function __construct()
    {
        self::$secret = getenv('JWT_SECRET');
        if (empty(self::$secret)) {
            throw new \RuntimeException('JWT_SECRET is not configured in .env');
        }
    }

    public function process(Request $request, callable $next): Response
    {

        Log::info('loadding middleware');
        $ip = method_exists($request, 'getRealIp')
            ? $request->getRealIp()
            : ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        Log::debug('request from IP: ' . $ip);

        $token = $this->extractToken($request);
        if (!$token) {
            return $this->unauthorizedResponse('Missing authorization token');
        }

        try {
            $decoded = $this->decodeToken($token);
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

    protected function extractToken(Request $request): ?string
    {
        $authHeader = $request->header('Authorization', '');
        if (preg_match('/^Bearer\s+(\S+)$/', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }

    protected function decodeToken(string $token): object
    {
        return JWT::decode($token, new Key(self::$secret, self::$algorithm));
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
        return new Response($code, ['WWW-Authenticate' => 'Bearer'], json_encode(['message' => 'Unauthorized: Invalid or missing token'], JSON_UNESCAPED_UNICODE));
    }
}
