<?php

namespace app\controller\v1;

use app\helper\IpAddressHelper;
use app\model\User;
use app\repositories\user\UserInterface;
use app\validation\user\UserValidate;
use support\Request;
use support\Response;
use support\Log;
use Workerman\Events\Uv;

class UserController
{
    protected UserValidate $validator;
    protected UserInterface $interface;
    public function __construct(UserInterface $interface, UserValidate $validator)
    {
        $this->validator = $validator;
        $this->interface = $interface;
    }

    // GET /search?q=keyword&page=1&per_page=15
    public function search(Request $request)
    {
        try {
            $keyword = $request->input('q', '');
            $perPage = (int)$request->input('per_page', 15);
            $paginated = $this->interface->search($keyword, $perPage);

            return new Response(200, Response::$HEADERS_JSON, json_encode([
                'success' => true,
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
            return new Response(500, Response::$HEADERS_JSON, json_encode(['success' => false, 'error' => 'Server error'], JSON_UNESCAPED_UNICODE));
        }
    }

    // GET /api/v1/users
    public function index(Request $request)
    {
        try {
            $perPage = (int)$request->input('per_page', 15);
            $paginated = $this->interface->listUsers($perPage);
            return new Response(200, Response::$HEADERS_JSON, json_encode([
                'success' => true,
                'data' => $paginated->items(),
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ]
            ], JSON_UNESCAPED_UNICODE));
            // return $this->repo->test();
        } catch (\Throwable $e) {
            Log::error('UserController@index error: ' . $e->getMessage());
            return new Response(500, Response::$HEADERS_TEXT, json_encode(['success' => false, 'error' => 'Server error'], JSON_UNESCAPED_UNICODE));
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
                $user = $this->interface->findByID($id);
            } else {
                $username = $identifier;
                $user = $this->interface->findByUsername($username);
            }

            if (!$user) {
                return new Response(
                    404,
                    Response::$HEADERS_JSON,
                    json_encode(['success' => false, 'error' => 'User not found'], JSON_UNESCAPED_UNICODE)
                );
            }

            return new Response(
                200,
                Response::$HEADERS_JSON,
                json_encode(['success' => true, 'data' => $user->toArray()], JSON_UNESCAPED_UNICODE)
            );
        } catch (\Throwable $e) {
            Log::error('UserController@show error: ' . $e->getMessage());
            return new Response(
                500,
                Response::$HEADERS_JSON,
                json_encode(['success' => false, 'error' => 'Server error'], JSON_UNESCAPED_UNICODE)
            );
        }
    }

    // POST /api/v1/users/register
    public function create(Request $request)
    {
        $data = $request->json();
        $dataOnly  = $request->only(['username', 'password']);

        $error = $this->validator->validateCreate($dataOnly);
        if (!empty($error)) {
            return new Response(400, Response::$HEADERS_JSON, json_encode(['error' => $error], JSON_UNESCAPED_UNICODE));
        }
        try {
            $user = $this->interface->register($this->prepareRegistration($data, IpAddressHelper::getRequestIp($request)));
            return new Response(201, Response::$HEADERS_JSON, json_encode(['success' => true, 'user_id' => $user->id], JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            Log::error('UserController@register error: ' . $e->getMessage());
            return new Response(500, Response::$HEADERS_JSON, json_encode(['error' => 'Cannot create user'], JSON_UNESCAPED_UNICODE));
        }
    }

    // POST /api/v1/users/password
    public function changePassword(Request $request)
    {
        $userId = $request->attributes['user_id'] ?? null;
        if (!$userId) {
            return new Response(
                401,
                Response::$HEADERS_JSON,
                json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE)
            );
        }

        $data = $request->json();
        if (empty($data['old_password']) || empty($data['new_password'])) {
            return new Response(
                400,
                Response::$HEADERS_JSON,
                json_encode(['error' => 'Missing old_password or new_password'], JSON_UNESCAPED_UNICODE)
            );
        }

        if ($this->interface->changePassword($userId, $data['old_password'], $data['new_password'])) {
            return new Response(
                200,
                Response::$HEADERS_JSON,
                json_encode(['success' => true], JSON_UNESCAPED_UNICODE)
            );
        }

        return new Response(
            403,
            Response::$HEADERS_JSON,
            json_encode(['error' => 'Old password incorrect'], JSON_UNESCAPED_UNICODE)
        );
    }

    // PATCH /api/v1/users/last_login
    public function updateLastLogin(Request $request)
    {
        try {
            $userId = $request->attributes['user_id'] ?? null;
            if (!$userId) {
                return new Response(
                    401,
                    Response::$HEADERS_JSON,
                    json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE)
                );
            }

            $data = $request->json();
            $payload = [
                'last_time' => $data['last_time'] ?? date('Y-m-d H:i:s'),
                'last_ip'   => IpAddressHelper::getRequestIp($request)
            ];

            if ($this->interface->updateLastLogin($userId, $payload)) {
                return new Response(
                    200,
                    Response::$HEADERS_JSON,
                    json_encode(array_merge(['success' => true], $payload), JSON_UNESCAPED_UNICODE)
                );
            }

            return new Response(
                500,
                Response::$HEADERS_JSON,
                json_encode(['error' => 'Cannot update last login'], JSON_UNESCAPED_UNICODE)
            );
        } catch (\Throwable $e) {
            Log::error('UserController@updateLastLogin error: ' . $e->getMessage());
            return new Response(
                500,
                Response::$HEADERS_JSON,
                json_encode(['error' => 'Server error'], JSON_UNESCAPED_UNICODE)
            );
        }
    }

    /** Helpers **/
    protected function prepareRegistration(array $data, string $ip): array
    {
        $now = date('Y-m-d H:i:s');
        return array_merge($data, [
            'password'  => password_hash($data['password'], PASSWORD_BCRYPT),
            'join_time' => $now,
            'join_ip'   => $ip,
        ]);
    }
}
