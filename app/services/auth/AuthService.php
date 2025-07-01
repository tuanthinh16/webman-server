<?php

namespace app\services\auth;

use support\Response;

class AuthService
{
    /** @var AuthStrategyInterface[] */
    private array $strategies;

    public function __construct(iterable $strategies)
    {
        $this->strategies = $strategies;
    }

    public function handle(array $data, string $ip)
    {
        $provider = $data['provider'] ?? 'credentials';
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($provider)) {
                return $strategy->handle($data, $ip);
            }
        }

        return new Response(400, Response::$HEADERS_JSON, json_encode([
            'status' => false,
            'message' => 'Unsupported authentication provider',
        ], JSON_UNESCAPED_UNICODE));
    }
}
