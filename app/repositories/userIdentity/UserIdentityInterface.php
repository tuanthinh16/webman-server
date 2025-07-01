<?php


namespace app\repositories\userIdentity;

interface UserIdentityInterface
{
    public function findByID(int $id);
    public function findByProviderAndUserID(string $provider, int $userId);
    public function findByProviderAndProviderUserID(string $provider, string $providerUserId);
    public function findByUserID(int $userId);
    public function findByProvider(string $provider);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function exists(string $provider, string $providerUserId): bool;
    public function getAllIdentities(int $userId);
    public function firstOrCreate(array $attributes, array $values = []);
}
