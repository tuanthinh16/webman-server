<?php

namespace app\services;


interface OtpServiceInterface
{

    /**
     * send the OTP to the user via email or SMS.
     *
     * @param integer $userId
     * @param string $otp
     */
    public function send(int $userId, string $otp, $email);
}
