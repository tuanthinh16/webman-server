<?php

namespace app\helper;

class OtpCodeHelper
{
    /**
     * Generate a random OTP code.
     *
     * @param int $length Length of the OTP code.
     * @return string
     */
    public static function generateOtp(int $length = 6): string
    {
        // Bộ ký tự: chữ hoa, chữ thường, số
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $max   = strlen($chars) - 1;
        $otp   = '';

        for ($i = 0; $i < $length; $i++) {
            $otp .= $chars[random_int(0, $max)];
        }

        return $otp;
    }
}
