<?php

namespace app\services;


class CofirmMailSender
{
    protected $mailService;

    public function __construct()
    {
        $this->mailService = new MailService();
    }

    public function send($username, $email, $startTime, $workplace, $confirmUrl, $subject)
    {

        // Encode subject to UTF-8 with proper mail header
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        // Prepare email content
        $body = view('mail-service', [
            'name' => $username,
            'start_time' => $startTime ?? date('Y-m-d', strtotime('+1 days')),
            'workplace' => $workplace ?? 'Webman',
            'confirm_url' => $confirmUrl ?? 'https://' . env('SERVER_HOST') . '/confirm?token=',

        ]);

        return $this->mailService->send($email, $encodedSubject, $body);
    }
}
