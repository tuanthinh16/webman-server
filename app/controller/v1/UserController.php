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
    protected UserInterface $userInterface;
    public function __construct(UserInterface $userInterface)
    {
        $this->userInterface = $userInterface;
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
            return new Response(500, Response::$HEADERS_JSON, json_encode(['status' => false, 'error' => 'Server error'], JSON_UNESCAPED_UNICODE));
        }
    }

    // GET /api/v1/users
    public function index(Request $request)
    {
        try {
            $perPage = (int)$request->input('per_page', 15);
            $paginated = $this->userInterface->listUsers($perPage);

            // Nếu trả về paginator (LengthAwarePaginator)
            if (is_object($paginated) && method_exists($paginated, 'items')) {
                $data = $paginated->items();
                $pagination = [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ];
            } else {
                // Nếu trả về mảng (không phân trang)
                $data = $paginated;
                $pagination = null;
            }

            return new Response(200, Response::$HEADERS_JSON, json_encode([
                'success' => true,
                'data' => $data,
                'pagination' => $pagination,
            ], JSON_UNESCAPED_UNICODE));
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
                $user = $this->userInterface->findByID($id);
            } else {
                $username = $identifier;
                $user = $this->userInterface->findByUsername($username);
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
        $data = $request->only(['username', 'password']);
        $validated = UserValidate::validate($data);

        if ($validated['status'] === false) {
            $error = $validated['errors'];
        }
        try {
            $user = $this->userInterface->register($this->prepareRegistration($data, IpAddressHelper::getRequestIp($request)));
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

        if ($this->userInterface->changePassword($userId, $data['old_password'], $data['new_password'])) {
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

    /** Helpers **/
    protected function prepareRegistration(array $data, string $ip): array
    {
        $now = date('Y-m-d H:i:s');
        return array_merge($data, [
            'password'  => password_hash($data['password'], PASSWORD_BCRYPT),
            'join_time' => $now,
            'join_ip'   => $ip,
            'nickname' => $data['username'] ?? ''
        ]);
    }
}
