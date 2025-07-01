<?php

namespace app\controller\v1;

use app\helper\IpAddressHelper;
use app\services\auth\AuthService;
use support\Request;


class AuthController
{
    public function login(Request $request)
    {
        $data     = $request->only(['username', 'password', 'provider', 'email', 'access_token', 'refresh_token', 'provider_user_id', 'expires_in', 'name']);
        $ip       = IpAddressHelper::getRequestIp($request);
        $auth = new AuthService();
        $response = $auth->handle($data, $ip);
        return $response;
    }
}
