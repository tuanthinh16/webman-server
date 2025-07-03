<?php

namespace app\repositories\user;

use app\helper\PaginationHelper;
use app\model\User;
use Elastic\Elasticsearch\ClientBuilder;
use support\Log;
use support\Redis;
use support\Response;

class UserRepository implements UserInterface
{
    protected $cachePrefix;
    protected $esClient;
    protected $modelClass = User::class;
    protected $index = 'wa_users';
    private $keyRedis = 'users';

    public function __construct()
    {
        $this->esClient = ClientBuilder::create()
            ->setHosts([env('ESLASCTICSEARCH')])
            ->build();

        if (!isset($this->modelClass)) {
            throw new \Exception('Model class must be defined in repository');
        }
        $this->cachePrefix = $this->cachePrefix ?? strtolower(class_basename($this->modelClass)) . 's';
    }
    public function findByUsername(string $username)
    {
        return ($this->modelClass)::where('username', $username)->first();
    }
    public function findByEmail(string $email)
    {
        return ($this->modelClass)::where('email', $email)->first();
    }
    public function findByEmailWhereInactive(string $email)
    {
        return ($this->modelClass)::where('email', $email)->where('status', 0)->first();
    }
    public function listUsers(int $perPage = 15)
    {
        $page = (int)request()->input('page', 1);
        $data = ($this->modelClass)::select('id', 'username', 'email')->paginate($perPage, ['*'], 'page', $page);

        return $data;
    }
    public function findByID(int $id)
    {
        return ($this->modelClass)::find($id);
    }

    public function register(array $data)
    {
        try {
            $user = ($this->modelClass)::create($data);
            if (Redis::exists($this->keyRedis)) Redis::del($this->keyRedis);
            return $user;
        } catch (\Throwable $e) {
            Log::error('UserService@register error: ' . $e->getMessage());
            throw $e;
        }
    }
    public function changePassword(int $userId, string $oldPassword, string $newPassword): bool
    {
        $user = $this->findByID($userId);
        if (!$user || !password_verify($oldPassword, $user->password)) {
            return false;
        }
        $this->update($userId, ['password' => password_hash($newPassword, PASSWORD_BCRYPT)]);
        return true;
    }
    public function update(int $id, array $attributes)
    {
        return ($this->modelClass)::where('id', $id)->update($attributes);
    }

    public function search(string $keyword, int $perPage)
    {
        try {
            if (!$this->esClient->indices()->exists(['index' => $this->index])->asBool()) {
                $this->esClient->indices()->create([
                    'index' => $this->index,
                    'body'  => ['mappings' => ['properties' => [
                        'nickname' => ['type' => 'text'],
                        'username' => ['type' => 'text'],
                    ]]]
                ]);
            }
            $data = "";
            if (!Redis::exists($this->keyRedis)) {
                $data = User::orderBy('id', 'desc')->get()->toArray();
                Redis::set($this->keyRedis, json_encode($data));
            }
            $data = json_decode(Redis::get($this->keyRedis), true);
            if (!$data) {
                return new Response(404, Response::$HEADERS_JSON, json_encode(['status' => false, 'message' => 'Not Found User'], JSON_UNESCAPED_UNICODE));
            }

            $bulkParams = ['body' => []];
            foreach ($data as $user) {
                $bulkParams['body'][] = [
                    'index' => ['_index' => $this->index, '_id' => $user['id']]
                ];
                $bulkParams['body'][] = [
                    'id'          => $user['id'],
                    'nickname'    => $user['nickname'],
                    'username'    => $user['username'],
                    'email'       => $user['email'],
                    'created_at'  => $user['created_at'],
                    'updated_at'  => $user['updated_at'],
                    'join_time'   => $user['join_time'],
                    'join_ip'     => $user['join_ip'],
                    'last_time'   => $user['last_time'],
                    'last_ip'     => $user['last_ip'],
                    //.....
                ];
            }
            $this->esClient->bulk($bulkParams);
            $page = (int)request()->input('page', 1);
            $from = ($page - 1) * $perPage;
            $params = [
                'index' => $this->index,
                'body'  => [
                    'from' => $from,
                    'size' => 30,
                    'query' => [
                        'bool' => [
                            'should' => [
                                [
                                    'wildcard' => [
                                        'nickname' => [
                                            'value'             => "*{$keyword}*",
                                            'case_insensitive'  => true,
                                        ],
                                    ],
                                ],
                                [
                                    'wildcard' => [
                                        'username' => [
                                            'value'             => "*{$keyword}*",
                                            'case_insensitive'  => true,
                                        ],
                                    ],
                                ],
                            ],
                            'minimum_should_match' => 1,
                        ],
                    ],
                ],
            ];
            $response = $this->esClient->search($params);
            $hits = $response['hits']['hits'];
            return PaginationHelper::Pagination($hits, $perPage, $page);
        } catch (\Throwable $e) {
            Log::error('UserRepository@search error: ' . $e->getMessage());
            throw $e;
        }
    }
    public function updateLastLogin(int $userId, array $payload): bool
    {
        $user = $this->findByID($userId);
        if (!$user) {
            return false;
        }
        $user->last_time = $payload['last_time'];
        $user->last_ip = $payload['last_ip'];
        return $user->save();
    }
}
