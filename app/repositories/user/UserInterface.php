<?php

namespace app\repositories\user;

interface UserInterface
{
    /**
     * Find a user by their username.
     *
     * @param string $username
     * 
     */
    public function findByUsername(string $username);
    /**
     * Find a user by their email address.
     *
     * @param string $email
     */
    public function findByEmail(string $email);
    /**
     * Undocumented function
     *
     * @param string $email
     */
    public function findByEmailWhereInactive(string $email);
    /**
     * find a user by their ID.
     *
     * @param integer $id
     *
     */
    public function findByID(int $id);
    /**
     * Search users by keyword.
     *
     * @param string $keyword
     * @param int $perPage
     * 
     */
    public function search(string $keyword, int $perPage);
    /**
     * List users with pagination.
     *
     * @param int $perPage
     * 
     */
    public function listUsers(int $perPage);
    /**
     * Update the last login time and IP address for a user.
     *
     * @param int $userId
     * @param array $payload
     * @return bool
     */
    public function changePassword(int $userId, string $oldPassword, string $newPassword);
    /**
     * Update user attributes.
     *
     * @param int $id
     * @param array $attributes
     * 
     */
    public function update(int $id, array $attributes);
    /**
     * Register a new user.
     *
     * @param array $data
     * 
     */
    public function register(array $data);
    /**
     * Update last login
     *
     * @param integer $userId
     * @param array $payload
     */
    public function updateLastLogin(int $userId, array $payload);
}
