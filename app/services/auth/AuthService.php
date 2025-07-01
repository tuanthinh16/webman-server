<?php

namespace app\services\auth;

use app\helper\JwtHelper;
use app\helper\PrepareDataUserHelper;
use App\Model\User;
use App\Model\UserIdentity;
use app\repositories\user\UserRepository;
use app\repositories\userIdentity\UserIdentityRepository;
use app\services\otp\OtpService;
use app\validation\user\UserValidate;
use support\Log;
use support\Response;

class AuthService
{
    private static $secret;
    private static $algo = 'HS256';
    private $userRepository;
    private $jwtHelper;
    private $ip;
    private $userIdentityRepository;

    public function __construct()
    {
        $this->jwtHelper = new JwtHelper();
        $this->userRepository = new UserRepository();
        self::$secret = env('JWT_SECRET', 'fallback_secret');
        $this->userIdentityRepository = new UserIdentityRepository();
    }
    public function handle(array $data, string $ip): Response
    {
        $this->ip = $ip;

        $provider = $data['provider'] ?? 'local';

        switch ($provider) {
            case 'credentials':
                return $this->localLogin($data);
            case 'google':
            case 'facebook':
                return $this->oauthLogin($provider, $data);
            default:
                return new Response(400, [], json_encode(['error' => 'Unsupported provider']));
        }
    }

    protected function localLogin($data): Response
    {
        try {
            $username = $data['username'];
            $password = $data['password'];

            $user = $this->userRepository->findByUsername($username);

            if (! $user || ! password_verify($password, $user->password)) {
                return new Response(400, Response::$HEADERS_JSON, json_encode([
                    'status' => false,
                    'message' => 'Invalid username or password'
                ], JSON_UNESCAPED_UNICODE));
            }
            // return $user;
            $now = date('Y-m-d H:i:s');
            $payload = [
                'last_time' => $now,
                'last_ip'   => $this->ip
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
                return new Response(200, Response::$HEADERS_JSON, json_encode([
                    'status' => true,
                    'token'   => $jwt,
                    'username' => $user->username,
                ], JSON_UNESCAPED_UNICODE));
            }
            return new Response(500, Response::$HEADERS_JSON, json_encode([
                'status' => false,
                'message' => 'Failed to login'
            ], JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            Log::error('AuthService@ Login error: ' . $e->getMessage(), [
                'data' => $data,
                'ip'   => $this->ip,
            ]);
            return Response::ServerError();
        }
    }

    protected function oauthLogin(string $provider, array $data): Response
    {
        try {

            if (empty($data['provider_user_id'])) {
                return new Response(400, [], json_encode(['error' => 'Missing provider_user_id']));
            }

            $identity = $this->userIdentityRepository->firstOrCreate([
                'provider'          => $provider,
                'provider_user_id'  => $data['provider_user_id'],
            ], [
                'provider_id'    => \App\Model\AuthProvider::getIdBySlug($provider),
                'credential'    => $data['access_token'] ?? null,
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_at'    => isset($data['expires_in']) ? date('Y-m-d H:i:s', time() + $data['expires_in']) : null,
                'extra'         => json_encode($data),
            ]);
            if (!$identity->user_id) {
                // nếu đăng nhập lần đầu
                $user = $this->userRepository->findByEmail($data['email']);
                if ($user) {
                    // nếu đã có user với email này -> liên kết tài khoản social với user hiện tại
                    $identity->user_id = $user->id;
                } else {
                    $data_user = [
                        'username' => $data['email'] ?? $data['provider_user_id'],
                        'email'    => $data['email'] ?? null,
                        'password' => null,
                        'ip'       => $this->ip,
                    ];
                    $validated = UserValidate::validate($data_user);
                    if (!$validated['status']) {
                        return new Response(400, Response::$HEADERS_JSON, json_encode(['status' => false, 'message' => $validated['message']]));
                    }
                    //register
                    $prepared_data = PrepareDataUserHelper::prepareRegistration($data_user, $this->ip);
                    $user = $this->userRepository->register($prepared_data);
                    $identity->user_id = $user->id;
                    //send otp
                    $otpService = new OtpService();
                    $otpService->sendOtpRegister($user->id, $user->email);
                }
                try {
                    $this->userIdentityRepository->update($identity->id, [
                        'user_id' => $identity->user_id,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('AuthService@ OAuth link user error: ' . $e->getMessage(), [
                        'data' => $data,
                        'ip'   => $this->ip,
                    ]);
                    return Response::ServerError('Failed to link user identity');
                }
            } else {
                try {
                    $user = User::find($identity->user_id);
                    $identity->update([
                        'credential'    => $data['access_token'],
                        'refresh_token' => $data['refresh_token'] ?? $identity->refresh_token,
                        'expires_at'    => $identity->expires_at,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('AuthService@ OAuth update identity error: ' . $e->getMessage(), [
                        'data' => $data,
                        'ip'   => $this->ip,
                    ]);
                    return Response::ServerError('Failed to update user identity');
                }
            }
            $payload = [
                'iss' => request()->host(),
                'iat' => time(),
                'exp' => time() + 36000,
                'sub' => $user->id,
                'role' => $user->role,
            ];
            $jwt = $this->jwtHelper->generateToken($payload);

            return new Response(
                200,
                Response::$HEADERS_JSON,
                json_encode([
                    'status' => true,
                    'token' => $jwt,
                    'user_id' => $identity->user_id
                ], JSON_UNESCAPED_UNICODE)
            );
        } catch (\Throwable $e) {
            Log::error('AuthService@ OAuth login error: ' . $e->getMessage(), [
                'data' => $data,
                'ip'   => $this->ip,
            ]);
            return Response::ServerError();
        }
    }
}
