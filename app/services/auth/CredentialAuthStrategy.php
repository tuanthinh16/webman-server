<?php

namespace app\services\auth;

use app\helper\JwtHelper;
use app\repositories\user\UserRepository;
use support\Log;
use support\Response;

class CredentialAuthStrategy implements AuthStrategyInterface
{
    private UserRepository $userRepository;
    private JwtHelper $jwtHelper;

    public function __construct(UserRepository $userRepository, JwtHelper $jwtHelper)
    {
        $this->userRepository = $userRepository;
        $this->jwtHelper = $jwtHelper;
    }

    public function supports(string $provider): bool
    {
        return $provider === 'credential';
    }
    /**
     * Handle Login with username and password
     *
     * @param array $data[username,password,email]
     * @param string $ip
     */
    public function handle(array $data, string $ip)
    {
        try {
            $username = $data['username'];
            $password = $data['password'];

            $user = $this->userRepository->findByUsername($username);

            if (! $user || ! password_verify($password, $user->password)) {
                return Response::UnAuthorize('Invalid username or password');
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
                $response = [
                    'username' => $user->username,
                    'user_id' => $user->id,
                ];
                return Response::LoginSuccess($jwt, $response);
            }
            return Response::ServerError('Fail to login');
        } catch (\Throwable $e) {
            Log::error('AuthService@ Login error: ' . $e->getMessage(), [
                'data' => $data,
                'ip'   => $ip,
            ]);
            return Response::ServerError();
        }
    }
}
