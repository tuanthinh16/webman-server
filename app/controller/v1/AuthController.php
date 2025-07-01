<?php

namespace app\controller\v1;

use app\helper\IpAddressHelper;
use app\services\auth\AuthService;
use support\Request;

class AuthController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        $data = $request->json();
        // return $data;
        $ip = IpAddressHelper::getRequestIp($request);

        return $this->authService->handle($data, $ip);
    }
}
