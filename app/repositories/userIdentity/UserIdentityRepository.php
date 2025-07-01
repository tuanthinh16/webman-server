<?php


namespace app\repositories\userIdentity;

use App\Model\AuthProvider;

class UserIdentityRepository implements UserIdentityInterface
{
    protected $model;

    public function __construct()
    {
        $this->model = new \app\model\UserIdentity();
    }

    public function findByID(int $id)
    {
        return $this->model->find($id);
    }

    public function findByProviderAndUserID(string $provider, int $userId)
    {
        $providerId = AuthProvider::getIdBySlug($provider);
        return $this->model->where('provider_id', $providerId)->where('user_id', $userId)->first();
    }

    public function findByProviderAndProviderUserID(string $provider, string $providerUserId)
    {
        $providerId = AuthProvider::getIdBySlug($provider);
        return $this->model->where('provider_id', $providerId)->where('provider_user_id', $providerUserId)->first();
    }

    public function findByUserID(int $userId)
    {
        return $this->model->where('user_id', $userId)->get();
    }

    public function findByProvider(string $provider)
    {
        $providerId = AuthProvider::getIdBySlug($provider);
        return $this->model->where('provider_id', $providerId)->get();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        return tap($this->findByID($id))->update($data);
    }

    public function delete(int $id)
    {
        return tap($this->findByID($id))->delete();
    }

    public function exists(string $provider, string $providerUserId): bool
    {
        $providerId = AuthProvider::getIdBySlug($provider);
        return (bool) $this->model
            ->where('provider_id', $providerId)
            ->where('provider_user_id', $providerUserId)
            ->exists();
    }

    public function getAllIdentities(int $userId)
    {
        return $this->model->where('user_id', $userId)->get();
    }
    public function firstOrCreate(array $attributes, array $values = [])
    {
        if (isset($attributes['provider'])) {
            $attributes['provider_id'] = AuthProvider::getIdBySlug($attributes['provider']);
            unset($attributes['provider']);
        }
        if (isset($values['provider'])) {
            $values['provider_id'] = AuthProvider::getIdBySlug($values['provider']);
            unset($values['provider']);
        }
        return $this->model->firstOrCreate($attributes, $values);
    }
}
