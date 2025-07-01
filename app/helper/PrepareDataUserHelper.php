<?php

namespace app\helper;

class PrepareDataUserHelper
{
    /**
     * Prepare data for user registration.
     *
     * @param array $data
     * @param string $ip
     * @return array
     */
    public static function prepareRegistration(array $data, string $ip): array
    {
        $now = date('Y-m-d H:i:s');
        return array_merge($data, [
            'password'  => empty($data['password']) ? null : password_hash($data['password'], PASSWORD_BCRYPT),
            'join_time' => $now,
            'join_ip'   => $ip,
            'status'    => 0,
            'nickname' => $data['username'] ?? ''
        ]);
    }
}
