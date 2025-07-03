<?php

namespace app\services\otp;

use app\helper\OtpCodeHelper;
use app\repositories\otp\OtpRepository;
use app\services\EmailOtpSender;
use app\validation\user\OtpValidate;
use support\Log;
use support\Response;

class OtpService
{
    private $otpHelper;
    private $otpRepository;
    private string $secret;
    public function __construct()
    {
        $this->otpHelper = new OtpCodeHelper();
        $this->otpRepository = new OtpRepository();
        $this->secret = env('JWT_SECRET', 'daylamabaomat');
    }
    public function sendOtpRegister($user_id, $user_email)
    {
        try {
            $createdAt = date('Y-m-d H:i:s');
            $otpCode   = OtpCodeHelper::generateOtp(6);
            $hash      = hash_hmac(
                'sha256',
                "{$user_id}|{$otpCode}|{$createdAt}",
                $this->secret
            );
            $otp = $this->otpRepository->create([
                'user_id'    => $user_id,
                'otp_code'   => $otpCode,
                'created_at' => $createdAt,
                'valid_time' => date('Y-m-d H:i:s', strtotime('+10 minutes')),
                'is_used'    => 0,
                'hash'       => $hash,
                'type'       => 'register',
            ]);

            if (!$otp) {
                $error = 'OTP creation failed';
                Log::error('UserController@register error: ' . $error);
                return Response::ServerError();
            }
            $mailService = new EmailOtpSender();
            $result = $mailService->send($user_id, $otpCode, $user_email, 'Xác nhận đăng ký tài khoản');
            return $result;
        } catch (\Throwable $e) {
            return $e;
        }
    }
    public function validateOtp($userID, $otpCode, $type = 'register')
    {
        try {
            $otp = $this->otpRepository->findByUserIdAndOtp($userID, $otpCode, $type);
            if (!$otp || strtotime($otp->valid_time) < time() || $otp->otp_code !== $otpCode) {
                return false;
            }
            $hash = hash_hmac(
                'sha256',
                "{$otp->user_id}|{$otpCode}|{$otp->created_at}",
                $this->secret
            );
            if ($hash !== $otp->hash) {
                return false;
            }
            $this->otpRepository->markAsUsed($otp->id);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
