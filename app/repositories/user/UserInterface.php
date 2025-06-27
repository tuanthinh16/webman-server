<?php

namespace app\repositories\user;

interface UserInterface
{

    public function findByUsername(string $username);
    public function findByID(int $id);
    public function search(string $keyword, int $perPage);
    public function listUsers(int $perPage);
    public function changePassword(int $userId, string $oldPassword, string $newPassword): bool;
    public function update($id, array $attributes);
    public function register(array $data);
    public function clearCache(): bool;
    public function paginate(int $perPage = 15, array $columns = ['*']);
    // public function getByIdOrUsername(int $id, string $username);
    public function updateLastLogin(int $userId, array $payload): bool;
}
