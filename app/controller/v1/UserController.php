<?php

namespace app\controller\v1;

use app\helper\IpAddressHelper;
use app\helper\OtpCodeHelper;
use app\helper\PrepareDataUserHelper;
use app\repositories\otp\OtpRepository;
use app\repositories\user\UserInterface;
use app\services\EmailOtpSender;
use app\services\MailService;
use app\services\otp\OtpService;
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

    /**
     * Search users by keyword with pagination.
     *      * Authorization: Bearer token
     *
     * GET /search?q=keyword&page=1&per_page=15
     *
     * @param Request $request Incoming HTTP request containing 'q' and pagination parameters.
     * @return Response JSON response with list of users and pagination metadata.
     */
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
    /**
     * List all users, optionally paginated.
     *
     * GET /api/v1/users
     *     * Authorization: Bearer token
     * @param Request $request Incoming HTTP request containing 'per_page'.
     * @return Response JSON response with user list and optional pagination.
     */
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

    /**
     * Retrieve a single user by ID or username.
     *
     * GET /api/v1/users/{id} or /api/v1/users/{username}
     *      * Authorization: Bearer token
     *
     * @param Request $request Incoming HTTP request.
     * @param string $identifier Numeric ID or username.
     * @return Response JSON response with user data or 404 if not found.
     */
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
    /**
     * Register a new user and send OTP for email verification.
     *
     * POST /api/v1/users/register
     * Body: username, password, email
     *
     * @param Request $request Incoming HTTP request with registration data.
     * @return Response JSON response with status, user_id, and OTP send status.
     */
    public function create(Request $request)
    {
        $data = $request->only(['username', 'password', 'email']);
        $validated = UserValidate::validate($data);

        if (!$validated['status']) {
            return json($validated, 422);
        }

        try {
            $user = $this->userInterface->register(PrepareDataUserHelper::prepareRegistration($data, IpAddressHelper::getRequestIp($request)));
            if (!$user) {
                $error = 'User registration failed';
                Log::error('UserController@register error: ' . $error);
                return new Response(500, Response::$HEADERS_JSON, json_encode(['status' => false, 'message' => $error], JSON_UNESCAPED_UNICODE));
            }
            $otpService = new OtpService();
            $result = $otpService->sendOtpRegister($user->id, $user->email);
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
    /**
     * Resend OTP to unverified user.
     *
     * POST /auth/v1/re-send-otp
     * Body: email
     *
     * @param Request $request Incoming HTTP request containing email.
     * @return Response JSON response with resend status or 404 if user not found.
     */
    public function reSendOtp(Request $request)
    {
        try {
            $email = $request->input('email');
            $user = $this->userInterface->findByEmailWhereInactive($email);
            return json($user);
            if (!$user) {
                return new Response(404, Response::$HEADERS_JSON, json_encode(['status' => false, 'message' => 'Not found user need active with email ' . $email]));
            }
            $otpService = new OtpService();
            $result = $otpService->sendOtpRegister($user->id, $email);
            return new Response(200, Response::$HEADERS_JSON, json_encode(['status' => true, 'message' => $result]));
        } catch (\Throwable $e) {
            Log::error('UserController@reSendOtp error: ' . $e->getMessage());
            return Response::ServerError($error ?? 'Server error');
        }
    }
    /**
     * Confirm user registration via OTP code.
     *
     * POST /api/v1/users/confirm
     * Body: user_id, otp
     *
     * @param Request $request Incoming HTTP request with OTP data.
     * @return Response JSON response confirming validation or error.
     */
    public function confirmRegister(Request $request)
    {
        try {
            $data = $request->json();
            if (empty($data['otp']) || empty($data['user_id'])) {
                return new Response(
                    400,
                    Response::$HEADERS_JSON,
                    json_encode(['status' => false, 'message' => 'Missing otp or user_id'], JSON_UNESCAPED_UNICODE)
                );
            }

            $otp = $this->otpRepository->findByUserIdAndOtp($data['user_id'], $data['otp'], 'register');
            if (!$otp || strtotime($otp->valid_time) < time() || $otp->otp_code !== $data['otp']) {
                return new Response(
                    404,
                    Response::$HEADERS_JSON,
                    json_encode(['message' => 'Invalid OTP'], JSON_UNESCAPED_UNICODE)
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
                    json_encode(['status' => false, 'message' => 'Invalid OTP'], JSON_UNESCAPED_UNICODE)
                );
            }
            $this->userInterface->update($data['user_id'], ['status' => 1]);
            $this->otpRepository->markAsUsed($otp->id);

            return new Response(
                200,
                Response::$HEADERS_JSON,
                json_encode(['status' => true, 'message' => 'User has been validated'], JSON_UNESCAPED_UNICODE)
            );
        } catch (\Throwable $e) {
            Log::error('UserController@confirmRegister error: ' . $e->getMessage());
            return Response::ServerError();
        }
    }
    /**
     * Change the authenticated user's password.
     *
     * POST /api/v1/users/password
     * Body: old_password, new_password
     * Authorization: Bearer token
     *
     * @param Request $request Incoming HTTP request with password data.
     * @return Response JSON response with success or error message.
     */
    public function changePassword(Request $request)
    {
        try {
            $userId = $request->attributes['user_id'] ?? null;
            if (!$userId) {
                return new Response(
                    401,
                    Response::$HEADERS_JSON,
                    json_encode(['status' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE)
                );
            }

            $data = $request->json();
            if (empty($data['old_password']) || empty($data['new_password'])) {
                return new Response(
                    400,
                    Response::$HEADERS_JSON,
                    json_encode(['status' => false, 'message' => 'Missing old_password or new_password'], JSON_UNESCAPED_UNICODE)
                );
            }

            if ($this->userInterface->changePassword($userId, $data['old_password'], $data['new_password'])) {
                return new Response(
                    200,
                    Response::$HEADERS_JSON,
                    json_encode(['status' => true, 'message' => 'Password has been updated'], JSON_UNESCAPED_UNICODE)
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
