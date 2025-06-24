<?php

namespace app\controller\v1;

use support\Request;
use support\Response;
use support\DB;
use Firebase\JWT\JWT;
use support\Log;

class AuthController
{
    private static $secret;
    private static $algo = 'HS256';

    public function __construct()
    {
        self::$secret = env('JWT_SECRET', 'fallback_secret');
    }

    public function login(Request $request)
    {
        $data     = $request->json();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (! $username || ! $password) {
            Log::warning("Login attempt with missing credentials. Username: {$username}");
            return new Response(
                406,
                ['Content-Type' => 'application/json'],
                json_encode(['message' => 'Missing credentials'], JSON_UNESCAPED_UNICODE)
            );
        }
        $ip = method_exists($request, 'getRealIp')
            ? $request->getRealIp()
            : ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        // Log::info('current IP: ' . $ip);
        // Fetch user from wa_users table
        $user = DB::table('wa_users')->where('username', $username)->first();
        if (! $user || ! password_verify($password, $user->password)) {

            Log::warning("Invalid login attempt. Username: {$username}, IP: {$ip}");
            return new Response(
                401,
                ['Content-Type' => 'application/json'],
                json_encode(['message' => 'Invalid credentials'], JSON_UNESCAPED_UNICODE)
            );
        }

        // Successful login: update last_time and last_ip in wa_users

        $now = date('Y-m-d H:i:s');
        DB::table('wa_users')
            ->where('id', $user->id)
            ->update([
                'last_time' => $now,
                'last_ip'   => $ip,
            ]);

        // Prepare JWT payload
        $payload = [
            'iss' => request()->host(),
            'iat' => time(),
            'exp' => time() + 36000,
            'sub' => $user->id,
            'role' => $user->role,
        ];

        $jwt = JWT::encode($payload, self::$secret, self::$algo);
        Log::info("User {$username} (ID: {$user->id}) logged in successfully.");

        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['token' => $jwt], JSON_UNESCAPED_UNICODE)
        );
    }
}
