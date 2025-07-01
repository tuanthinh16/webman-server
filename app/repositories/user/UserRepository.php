<?php

namespace app\repositories\user;

use app\model\User;
use Elastic\Elasticsearch\ClientBuilder;
use support\Log;
use support\Redis;

class UserRepository implements UserInterface
{
    protected $cachePrefix;
    protected $esClient;
    protected $modelClass = User::class;
    protected $index = 'wa_users';

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
            return ($this->modelClass)::create($data);
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
            if (!Redis::exists('users')) {
                $data = User::all()->toArray();

                Redis::set('users', json_encode($data));
            } else {
                $data = json_decode(Redis::get('users'), true);
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
            $params = [
                'index' => $this->index,
                'body'  => [
                    'query' => [
                        'bool' => [
                            'should' => [
                                ['wildcard' => ['nickname' => '*' . strtolower($keyword) . '*']],
                                ['wildcard' => ['username' => '*' . strtolower($keyword) . '*']],
                            ]
                        ]
                    ]
                ]
            ];
            $response = $this->esClient->search($params);
            $hits = isset($response['hits']['hits']) && is_array($response['hits']['hits'])
                ? array_map(fn($h) => $h['_source'], $response['hits']['hits'])
                : [];
            $page = request()->input('page', 1);

            $total = count($hits);
            $items = array_slice($hits, ($page - 1) * $perPage, $perPage);

            return new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->queryString()]
            );
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
