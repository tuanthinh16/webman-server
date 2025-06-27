<?php

namespace app\helper;

use support\Request;

class IpAddressHelper
{
    /**
     * Get the real IP address of the client.
     *
     * @param Request $request
     * @return string
     */
    public static function getRequestIp(Request $request): string
    {
        if (method_exists($request, 'getRealIp')) {
            return $request->getRealIp();
        }
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
