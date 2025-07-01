<?php

namespace app\services\auth;

use support\Response;

interface AuthStrategyInterface
{
    /**
     * Determine if this strategy supports the given provider type.
     *
     * @param string $provider
     * @return bool
     */
    public function supports(string $provider): bool;

    /**
     * Handle authentication for supported provider.
     *
     * @param array $data
     * @param string $ip
     */
    public function handle(array $data, string $ip);
}
