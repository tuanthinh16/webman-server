<?php

namespace app\services;

use app\repositories\user\UserRepository;
use Illuminate\Support\Facades\Mail;

class EmailOtpSender implements OtpServiceInterface
{
    protected $mailService;

    public function __construct()
    {
        $this->mailService = new MailService();
    }

    public function send(int $userId, string $otp, $email, $subject = 'Your OTP Code')
    {
        // Fetch user email from the database
        $user = (new UserRepository())->findByID($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Prepare email content
        $body = view('otp-service', [
            'name' => $user->username,
            'otp' => $otp
        ]);

        return $this->mailService->send($email, $subject, $body);
    }
}
