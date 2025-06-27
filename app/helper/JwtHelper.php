<?php

namespace app\helper;


class JwtHelper
{
    private static $algo = 'HS256';
    private static $secret;
    public function __construct()
    {
        self::$secret = self::$secret = env('JWT_SECRET', 'fallback_secret');
        if (empty(self::$secret)) {
            throw new \RuntimeException('JWT_SECRET is not configured in .env');
        }
    }
    /**
     * Generate a JWT token.
     *
     * @param array $payload
     * @param string $secretKey
     * @return string
     */
    public static function generateToken(array $payload): string
    {
        return \Firebase\JWT\JWT::encode($payload, self::$secret, self::$algo);
    }

    /**
     * Decode a JWT token.
     *
     * @param string $token
     * @param string $secretKey
     * @return object|null
     */
    public static function decodeToken(string $token): ?object
    {
        try {
            return \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key(self::$secret, 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
    }
}
