<?php

namespace app\controller\v1;

use app\helper\IpAddressHelper;
use app\repositories\user\AuthRepository;
use support\Request;
use support\Response;

use support\Log;

class AuthController
{
    public function login(Request $request)
    {
        $data     = $request->json();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (! $username || ! $password) {
            Log::warning("Login attempt with missing credentials. Username: {$username}");
            return new Response(
                406,
                Response::$HEADERS_JSON,
                json_encode(['message' => 'Missing credentials'], JSON_UNESCAPED_UNICODE)
            );
        }
        $ip = IpAddressHelper::getRequestIp($request);

        $authService = new AuthRepository();
        $result = $authService->validateCredentials($username, $password, $ip);
        return new Response(
            200,
            Response::$HEADERS_JSON,
            $result
        );
    }
}
