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

        // Encode subject to UTF-8 with proper mail header
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        // Prepare email content
        $body = view('otp-service', [
            'name' => $user->username,
            'otp' => $otp
        ]);

        return $this->mailService->send($email, $encodedSubject, $body);
    }
}
