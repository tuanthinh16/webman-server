<?php

namespace app\services\auth;

use support\Response;

class AuthService
{
    private AuthStrategyInterface $strategies;

    public function __construct(AuthStrategyInterface $strategies)
    {
        $this->strategies = $strategies;
    }

    public function handle(array $data, string $ip)
    {
        $provider = $data['provider'] ?? 'credentials';

        if ($this->strategies->supports($provider)) {
            return $this->strategies->handle($data, $ip);
        }
        return new Response(400, Response::$HEADERS_JSON, json_encode([
            'status' => false,
            'message' => 'Unsupported authentication provider',
        ], JSON_UNESCAPED_UNICODE));
    }
}
