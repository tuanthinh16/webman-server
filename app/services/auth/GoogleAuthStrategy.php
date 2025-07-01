<?php

namespace app\services\auth;

use app\helper\JwtHelper;
use app\helper\PrepareDataUserHelper;
use app\model\User;
use app\repositories\user\UserRepository;
use app\repositories\userIdentity\UserIdentityRepository;
use app\services\otp\OtpService;
use app\validation\user\UserValidate;
use support\Log;
use support\Response;

class GoogleAuthStrategy implements AuthStrategyInterface
{
    private UserIdentityRepository $userIdentityRepository;
    private JwtHelper $jwtHelper;
    private $otpService;
    private $provider;
    private $userRepository;
    public function __construct(
        UserIdentityRepository $identityRepo,
        JwtHelper $jwtHelper,
        OtpService $otpService
    ) {
        $this->userIdentityRepository = $identityRepo;
        $this->jwtHelper = $jwtHelper;
        $this->otpService = $otpService;
        $this->userRepository = new UserRepository();
    }

    public function supports(string $provider): bool
    {
        $this->provider = $provider;
        return $provider == 'google';
    }
    /**
     * Handle Gooogle Login and create OTP verify
     *
     * @param array $data[provider_user_id,provider,access_token,refresh_token,expires_in,email]
     * @param string $ip
     * 
     */
    public function handle(array $data, string $ip)
    {
        try {

            if (empty($data['provider_user_id'])) {
                return new Response(400, Response::$HEADERS_JSON, json_encode(['error' => 'Missing provider_user_id']));
            }

            $identity = $this->userIdentityRepository->firstOrCreate([
                'provider'          => $this->provider,
                'provider_user_id'  => $data['provider_user_id'],
            ], [
                'provider_id'    => \App\Model\AuthProvider::getIdBySlug($this->provider),
                'credential'    => $data['access_token'] ?? null,
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_at'    => isset($data['expires_in']) ? date('Y-m-d H:i:s', time() + $data['expires_in']) : null,
                'extra'         => json_encode($data),
            ]);
            if (!$identity->user_id) {
                // nếu đăng nhập lần đầu
                $user = $this->userRepository->findByEmail($data['email']);
                if ($user) {
                    // nếu đã có user với email này -> return
                    return new Response(400, Response::$HEADERS_JSON, json_encode(['status' => false, 'message' => 'Email has exits']));
                } else {
                    $data_user = [
                        'username' => $data['email'] ?? $data['provider_user_id'],
                        'email'    => $data['email'] ?? null,
                        'password' => null,
                        'ip'       => $ip,
                    ];
                    $validated = UserValidate::validate($data_user);
                    if (!$validated['status']) {
                        return new Response(400, Response::$HEADERS_JSON, json_encode(['status' => false, 'message' => $validated['message']]));
                    }
                    //register
                    $prepared_data = PrepareDataUserHelper::prepareRegistration($data_user, $ip);
                    $user = $this->userRepository->register($prepared_data);
                    $identity->user_id = $user->id;
                    //send otp
                    $this->otpService->sendOtpRegister($user->id, $user->email);
                }
                try {
                    $this->userIdentityRepository->update($identity->id, [
                        'user_id' => $identity->user_id,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('AuthService@ OAuth link user error: ' . $e->getMessage(), [
                        'data' => $data,
                        'ip'   => $ip,
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
                    Log::error('AuthService@ Gooogle update identity error: ' . $e->getMessage(), [
                        'data' => $data,
                        'ip'   => $ip,
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
            $response = [
                'username' => $user->username,
                'user_id' => $identity->user_id
            ];
            return Response::LoginSuccess($jwt, $response);
        } catch (\Throwable $e) {
            Log::error('AuthService@ Gooogle login error: ' . $e->getMessage(), [
                'data' => $data,
                'ip'   => $ip,
            ]);
            return Response::ServerError();
        }
    }
}
