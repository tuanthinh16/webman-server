<?php

namespace app\controller\v1;

use app\helper\IpAddressHelper;
use app\helper\PrepareDataUserHelper;
use app\repositories\otp\OtpRepository;
use app\repositories\user\UserInterface;
use app\services\otp\OtpService;
use app\validation\user\OtpValidate;
use app\validation\user\UserValidate;
use support\Request;
use support\Response;
use support\Log;
use support\Validation;

class UserController
{
    protected UserInterface $userInterface;
    protected OtpRepository $otpRepository;
    protected $otpService;
    public function __construct(UserInterface $userInterface)
    {
        $this->userInterface = $userInterface;
        $this->otpRepository = new OtpRepository();
        $this->otpService = new OtpService();
    }

    /**
     * Search users by keyword with pagination.
     *      * Authorization: Bearer token
     *
     * GET /search?q=keyword&page=1&per_page=15
     *
     * @param Request $request Incoming HTTP request containing 'q' and pagination parameters.
     */
    public function search(Request $request)
    {
        try {
            $keyword = $request->input('q', '');
            $perPage = $request->input('per_page', 15);
            $paginated = $this->userInterface->search($keyword, $perPage);
            return Response::Success($paginated);
        } catch (\Throwable $e) {
            Log::error('UserController@search error: ' . $e->getMessage());
            return $e->getMessage();
        }
    }
    /**
     * List all users, optionally paginated.
     *
     * GET /api/v1/users
     *     * Authorization: Bearer token
     * @param Request $request Incoming HTTP request containing 'per_page'.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $data = $this->userInterface->listUsers($perPage);
            return Response::Success($data);
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
            return Response::Success($user->toArray());
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
            return new Response(422, Response::$HEADERS_JSON, $validated);
        }
        try {
            $user = $this->userInterface->register(PrepareDataUserHelper::prepareRegistration($data, IpAddressHelper::getRequestIp($request)));
            if (!$user) {
                return new Response(500, Response::$HEADERS_JSON, json_encode(['status' => false, 'message' => 'User registration failed'], JSON_UNESCAPED_UNICODE));
            }
            $otpService = new OtpService();
            $result = $otpService->sendOtpRegister($user->id, $user->email);
            if (!$result) {
                return Response::ServerError('Failed to send OTP email');
            }
            return Response::Success(['send-otp' => $result, 'email' => $user->email], 201);
        } catch (\Throwable $e) {
            Log::error('UserController@register error: ' . $e->getMessage());
            return Response::ServerError();
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
            if (!$user) {
                return new Response(404, Response::$HEADERS_JSON, json_encode(['status' => false, 'message' => 'Not found user need active with email ' . $email]));
            }
            $result = $this->otpService->sendOtpRegister($user->id, $email, true);
            return Response::Success($result);
        } catch (\Throwable $e) {
            Log::error('UserController@reSendOtp error: ' . $e->getMessage());
            return Response::ServerError();
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
            $data = $request->only(['otp', 'email']);
            $validated = OtpValidate::validate($data);
            if (!$validated['status']) {
                return json($validated, 422);
            }
            $user = $this->userInterface->findByEmailWhereInactive($data['email']);
            if (!$user) {
                return new Response(404, Response::$HEADERS_JSON, json_encode(['status' => false, 'message' => 'Not found user need active with email ' . $data['email']]));
            }
            $result = $this->otpService->validateOtp($user->id, $data['otp']);
            if (!$result) return new Response(
                403,
                Response::$HEADERS_JSON,
                json_encode(['status' => false, 'message' => 'Invalid OTP'], JSON_UNESCAPED_UNICODE)
            );
            $this->userInterface->update($user->id, ['status' => 1]);
            return Response::Success('User has been validated');
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
                return Response::UnAuthorize();
            }
            $data = $request->only(['old_password', 'new_password']);
            if (empty($data['old_password']) || empty($data['new_password'])) {
                return new Response(
                    400,
                    Response::$HEADERS_JSON,
                    json_encode(['status' => false, 'message' => 'Missing old_password or new_password'], JSON_UNESCAPED_UNICODE)
                );
            }
            if ($this->userInterface->changePassword($userId, $data['old_password'], $data['new_password'])) {

                return Response::Success('Password has been updated');
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
