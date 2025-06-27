<?php


namespace app\repositories\user;

use app\helper\JwtHelper;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;

class AuthRepository
{
    private static $secret;
    private static $algo = 'HS256';
    private $userRepository;
    private $jwtHelper;
    public function __construct()
    {
        $this->jwtHelper = new JwtHelper();
        $this->userRepository = new UserRepository();
        self::$secret = env('JWT_SECRET', 'fallback_secret');
    }
    /**
     * Validate user credentials.
     *
     * @param string $username
     * @param string $password
     * @param string $ip
     * @throws \Exception
     * @return json
     */
    public function validateCredentials(string $username, string $password, string $ip)
    {
        try {
            $user = $this->userRepository->findByUsername($username);
            if (! $user || ! password_verify($password, $user->password)) {
                return json_encode([
                    'success' => false,
                    'message' => 'Invalid username or password'
                ], JSON_UNESCAPED_UNICODE);
            }
            $now = date('Y-m-d H:i:s');
            $payload = [
                'last_time' => $now,
                'last_ip'   => $ip
            ];
            if ($this->userRepository->updateLastLogin($user->id, $payload)) {
                $payload = [
                    'iss' => request()->host(),
                    'iat' => time(),
                    'exp' => time() + 36000,
                    'sub' => $user->id,
                    'role' => $user->role,
                ];

                $jwt = $this->jwtHelper->generateToken($payload);
                return json_encode([
                    'success' => true,
                    'token'   => $jwt,
                    'user_id' => $user->id,
                ], JSON_UNESCAPED_UNICODE);
            }
            return json_encode([
                'success' => false,
                'message' => 'Failed to login'
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            return json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
