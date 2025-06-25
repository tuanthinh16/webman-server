<?php

namespace app\controller\v1;

use app\model\User;   // Model mới, mapping tới wa_users
use support\Request;
use support\Response;
use support\Log;
use Illuminate\Database\Capsule\Manager as DB;
use Elastic\Elasticsearch\ClientBuilder;

use support\Redis;

class UserController
{
    protected $client;

    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->setHosts(['http://elasticsearch:9200'])
            ->build();
    }

    public function search($request)
    {
        $index = 'wa_users';
        $keyword = $request->input('q', '');

        if (!$keyword) {
            return new Response(400, ['Content-Type' => 'application/json'], json_encode([
                'error' => 'Missing search keyword'
            ], JSON_UNESCAPED_UNICODE));
        }
        if (!$this->client->indices()->exists(['index' => $index])->asBool()) {
            $this->client->indices()->create([
                'index' => $index,
                'body' => [
                    'mappings' => [
                        'properties' => [
                            'nickname' => ['type' => 'text'],
                            'level'    => ['type' => 'text']
                        ]
                    ]
                ]
            ]);
        }
        $data = "";
        if (!Redis::exists('userss')) {
            $data = User::all()->toArray();
            Redis::set('userss', json_encode($data));
        }
        $data = Redis::get('userss');
        return json(['data' => $data]);
        $bulkParams = ['body' => []];

        foreach ($data as $user) {
            $bulkParams['body'][] = [
                'index' => [
                    '_index' => $index,
                    '_id'    => $user['id'],
                ]
            ];
            $bulkParams['body'][] = [
                'nickname' => $user['nickname'] ?? '',
                'username'    => $user['username'] ?? ''
            ];
        }
        $this->client->bulk($bulkParams);
        $params = [
            'index' => $index,
            'body'  => [
                'query' => [
                    'multi_match' => [
                        'query'  => $keyword,
                        'fields' => ['nickname', 'username'],
                        'type'   => 'phrase'
                    ]
                ]
            ]
        ];

        $response = $this->client->search($params);

        return json($response['hits']['hits']);
    }
    /**
     * List all users (admin).
     */
    public function index(Request $request)
    {
        try {
            $users = User::all()->toArray();
            return json(['data' => $users], 200);
        } catch (\Throwable $e) {
            Log::error('UserController@index error: ' . $e->getMessage());
            return json(['error' => 'Server error'], 500);
        }
    }
    /**
     * Show a single user by id or username.
     * GET /api/user?id=123  hoặc  /api/user?username=alice
     */
    public function show(Request $request)
    {
        try {
            $id       = $request->input('id');
            $username = $request->input('username');

            if ($id) {
                $user = User::find($id);
            } elseif ($username) {
                $user = User::where('username', $username)->first();
            } else {
                return json([
                    'success' => false,
                    'error'   => 'Missing id or username'
                ], 400);
            }

            if (! $user) {
                return json([
                    'success' => false,
                    'error'   => 'User not found'
                ], 404);
            }

            return json([
                'success' => true,
                'data'    => $user->toArray()
            ], 200);
        } catch (\Throwable $e) {
            Log::error('UserController@show error: ' . $e->getMessage());
            return json(['success' => false, 'error' => 'Server error'], 500);
        }
    }

    /**
     * Register a new user.
     * POST /api/users/register
     * body JSON: { username, password, [nickname], [email], [mobile], [sex], [avatar] }
     */
    public function register(Request $request)
    {
        $data = $request->json();
        foreach (['username', 'password'] as $f) {
            if (empty($data[$f])) {
                return json(['error' => "Missing $f"], 400);
            }
        }

        try {
            $ip = method_exists($request, 'getRealIp')
                ? $request->getRealIp()
                : ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown');

            $now = date('Y-m-d H:i:s');

            $user = User::create([
                'username'   => $data['username'],
                'password'   => password_hash($data['password'], PASSWORD_BCRYPT),
                'nickname'   => $data['nickname'] ?? '',
                'email'      => $data['email'] ?? null,
                'mobile'     => $data['mobile'] ?? null,
                'sex'        => in_array($data['sex'] ?? '1', ['0', '1']) ? $data['sex'] : '1',
                'avatar'     => $data['avatar'] ?? null,
                'role'       => $data['role'] ?? 1,
                'join_time'  => $now,
                'join_ip'    => $ip,
            ]);

            return json(['success' => true, 'user_id' => $user->id], 201);
        } catch (\Throwable $e) {
            Log::error('UserController@register error: ' . $e->getMessage());
            return json(['error' => 'Cannot create user'], 500);
        }
    }

    /**
     * Change password for authenticated user.
     * POST /api/users/password
     * body JSON: { old_password, new_password }
     */
    public function changePassword(Request $request)
    {
        $userId = $request->attributes['user_id'] ?? null;
        if (! $userId) {
            return json(['error' => 'Unauthorized'], 401);
        }
        $data = $request->json();
        if (empty($data['old_password']) || empty($data['new_password'])) {
            return json(['error' => 'Missing old_password or new_password'], 400);
        }

        try {
            $user = User::find($userId);
            if (! $user || ! password_verify($data['old_password'], $user->password)) {
                return json(['error' => 'Old password incorrect'], 403);
            }
            $user->password = password_hash($data['new_password'], PASSWORD_BCRYPT);
            $user->save();

            return json(['success' => true], 200);
        } catch (\Throwable $e) {
            Log::error('UserController@changePassword error: ' . $e->getMessage());
            return json(['error' => 'Cannot change password'], 500);
        }
    }

    /**
     * Update last login time/IP for authenticated user.
     * PATCH /api/users/last_login
     * body JSON: { last_time (optional), last_ip (optional) }
     */
    public function updateLastLogin(Request $request)
    {
        $userId = $request->attributes['user_id'] ?? null;
        if (! $userId) {
            return json(['error' => 'Unauthorized'], 401);
        }
        $data = $request->json();
        $time = $data['last_time'] ?? date('Y-m-d H:i:s');
        // Lấy IP client, ưu tiên header X-Forwarded-For nếu có
        $ip = method_exists($request, 'getRealIp')
            ? $request->getRealIp()
            : ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown');


        try {
            User::where('id', $userId)
                ->update([
                    'last_time' => $time,
                    'last_ip'   => $ip
                ]);

            return json(['success' => true, 'last_time' => $time, 'last_ip' => $ip], 200);
        } catch (\Throwable $e) {
            Log::error('UserController@updateLastLogin error: ' . $e->getMessage());
            return json(['error' => 'Cannot update last login'], 500);
        }
    }
}
