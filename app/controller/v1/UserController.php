<?php

namespace app\controller\v1;

use app\helper\IpAddressHelper;
use app\helper\OtpCodeHelper;
use app\helper\PrepareDataUserHelper;
use app\repositories\otp\OtpRepository;
use app\repositories\user\UserInterface;
use app\services\EmailOtpSender;
use app\services\MailService;
use app\validation\user\UserValidate;
use support\Request;
use support\Response;
use support\Log;
use GuzzleHttp\Client;
        // $validated = UserValidate::validate($data);

class UserController
{
    protected UserInterface $userInterface;
    protected OtpRepository $otpRepository;
    public function __construct(UserInterface $userInterface)
    {
        $this->userInterface = $userInterface;
        $this->otpRepository = new OtpRepository();
    }
    public function testview()
    {
        return view('mail-service');
    }
    // GET /search?q=keyword&page=1&per_page=15
    public function search(Request $request)
    {
        try {
            $keyword = $request->input('q', '');
            $perPage = (int)$request->input('per_page', 15);
            $paginated = $this->userInterface->search($keyword, $perPage);

            return new Response(200, Response::$HEADERS_JSON, json_encode([
                'status' => true,
                'data' => $paginated->items(),
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ]
            ], JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            Log::error('UserController@search error: ' . $e->getMessage());
            return Response::ServerError();
        }
    }
    // GET /api/v1/users
    public function index(Request $request)
    {
        try {
            $perPage = (int)$request->input('per_page', 15);
            $data = $this->userInterface->listUsers($perPage);
            if (is_object($data) && method_exists($data, 'items')) {
                $result = $data->items();
                $pagination = [
                    'current_page' => $data->currentPage(),
                    'last_page'    => $data->lastPage(),
                    'per_page'     => $data->perPage(),
                    'total'        => $data->total(),
                ];
            } else {
                $result = $data;
                $pagination = null;
            }

            return new Response(200, Response::$HEADERS_JSON, json_encode([
                'status' => true,
                'data' => $result,
                'pagination' => $pagination,
            ], JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            Log::error('UserController@index error: ' . $e->getMessage());
            return Response::ServerError();
        }
    }

    // GET /api/v1/users/{id} or username
    public function show(Request $request, string $identifier)
    {
        try {
            // Determine if identifier is numeric (ID) or string (username)
            $user = null;
            if (ctype_digit($identifier)) {
                $id = (int) $identifier;
                $user = $this->userInterface->findByID($id);
            } else {
                $username = $identifier;
                $user = $this->userInterface->findByUsername($username);
            }

            if (!$user) {
                return new Response(
                    404,
                    Response::$HEADERS_JSON,
                    json_encode(['status' => false, 'message' => 'User not found'], JSON_UNESCAPED_UNICODE)
                );
            }

            return new Response(
                200,
                Response::$HEADERS_JSON,
                json_encode(['status' => true, 'data' => $user->toArray()], JSON_UNESCAPED_UNICODE)
            );
        } catch (\Throwable $e) {
            Log::error('UserController@show error: ' . $e->getMessage());
            return Response::ServerError();
        }
    }

    // POST /api/v1/users/register
    public function create(Request $request)
    {
        $data = $request->only(['username', 'password', 'email']);
            $validated = UserValidate::validate($data);
            
             if (!$validated['status']) {
                return json($validated, 422); 
            }
            return 111;
        try {
            $user = $this->userInterface->register(PrepareDataUserHelper::prepareRegistration($data, IpAddressHelper::getRequestIp($request)));
            if (!$user) {
                $error = 'User registration failed';
                Log::error('UserController@register error: ' . $error);
                return new Response(500, Response::$HEADERS_JSON, json_encode(['status' => false, 'message' => $error], JSON_UNESCAPED_UNICODE));
            }


            $createdAt = date('Y-m-d H:i:s');
            $otpCode   = OtpCodeHelper::generateOtp(6);

            $secret    = env('JWT_SECRET', 'some_random_secret');
            $hash      = hash_hmac(
                'sha256',
                "{$user->id}|{$otpCode}|{$createdAt}",
                $secret
            );

            $otp = $this->otpRepository->create([
                'user_id'    => $user->id,
                'otp_code'   => $otpCode,
                'created_at' => $createdAt,
                'valid_time' => date('Y-m-d H:i:s', strtotime('+10 minutes')),
                'is_used'    => 0,
                'hash'       => $hash,
                'type'       => 'register',
            ]);
            if (!$otp) {
                $error = 'OTP creation failed';
                Log::error('UserController@register error: ' . $error);
                return Response::ServerError();
            }
            // Send OTP to user email
            $mailService = new EmailOtpSender();
            $result = $mailService->send($user->id, $otpCode, $user->email, 'Xác nhận đăng ký tài khoản');
            if (!$result) {
                $error = 'Failed to send OTP email';
                Log::error('UserController@register error: ' . $error);
                return Response::ServerError();
            }
            return new Response(201, Response::$HEADERS_JSON, json_encode(['status' => true, 'user_id' => $user->id, 'send-otp' => $result, 'email' => $user->email], JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            Log::error('UserController@register error: ' . $e->getMessage());
            return Response::ServerError($error ?? 'Server error');
        }
    }
    // POST /api/v1/users/confirm
    public function confirmRegister(Request $request)
    {
        try {
            $data = $request->json();
            if (empty($data['otp']) || empty($data['user_id'])) {
                return new Response(
                    400,
                    Response::$HEADERS_JSON,
                    json_encode(['message' => 'Missing otp or user_id'], JSON_UNESCAPED_UNICODE)
                );
            }

            $otp = $this->otpRepository->findByUserIdAndOtp($data['user_id'], $data['otp'], 'register');
            // Validate user ID
            if (!$otp) {
                return new Response(
                    404,
                    Response::$HEADERS_JSON,
                    json_encode(['message' => 'OTP not found'], JSON_UNESCAPED_UNICODE)
                );
            }
            // Check if OTP is already used
            if ($otp->is_used) {
                return new Response(
                    403,
                    Response::$HEADERS_JSON,
                    json_encode(['message' => 'OTP already used'], JSON_UNESCAPED_UNICODE)
                );
            }
            // Check if OTP is expired
            if (strtotime($otp->valid_time) < time()) {
                return new Response(
                    410,
                    Response::$HEADERS_JSON,
                    json_encode(['message' => 'OTP expired'], JSON_UNESCAPED_UNICODE)
                );
            }
            //Verify OTP code
            if ($otp->otp_code !== $data['otp']) {
                return new Response(
                    403,
                    Response::$HEADERS_JSON,
                    json_encode(['message' => 'Invalid OTP code'], JSON_UNESCAPED_UNICODE)
                );
            }
            // Verify OTP hash
            $secret = env('JWT_SECRET', 'some_random_secret');
            $hash = hash_hmac(
                'sha256',
                "{$otp->user_id}|{$data['otp']}|{$otp->created_at}",
                $secret
            );
            if ($hash !== $otp->hash) {
                return new Response(
                    403,
                    Response::$HEADERS_JSON,
                    json_encode(['message' => 'Invalid OTP'], JSON_UNESCAPED_UNICODE)
                );
            }
            $this->userInterface->update($data['user_id'], ['status' => 1]);
            $this->otpRepository->markAsUsed($otp->id);

            return new Response(
                200,
                Response::$HEADERS_JSON,
                json_encode(['status' => true], JSON_UNESCAPED_UNICODE)
            );
        } catch (\Throwable $e) {
            Log::error('UserController@confirmRegister error: ' . $e->getMessage());
            return Response::ServerError();
        }
    }
    // POST /api/v1/users/password
    public function changePassword(Request $request)
    {
        try {
            $userId = $request->attributes['user_id'] ?? null;
            if (!$userId) {
                return new Response(
                    401,
                    Response::$HEADERS_JSON,
                    json_encode(['message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE)
                );
            }

            $data = $request->json();
            if (empty($data['old_password']) || empty($data['new_password'])) {
                return new Response(
                    400,
                    Response::$HEADERS_JSON,
                    json_encode(['message' => 'Missing old_password or new_password'], JSON_UNESCAPED_UNICODE)
                );
            }

            if ($this->userInterface->changePassword($userId, $data['old_password'], $data['new_password'])) {
                return new Response(
                    200,
                    Response::$HEADERS_JSON,
                    json_encode(['status' => true], JSON_UNESCAPED_UNICODE)
                );
            }

            return new Response(
                403,
                Response::$HEADERS_JSON,
                json_encode(['message' => 'Old password incorrect'], JSON_UNESCAPED_UNICODE)
            );
        } catch (\Throwable $e) {
            Log::error('UserController@changePassword error: ' . $e->getMessage());
            return Response::ServerError();
        }
    }
}
