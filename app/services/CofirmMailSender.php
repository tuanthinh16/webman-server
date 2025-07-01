<?php

namespace app\services;


class CofirmMailSender
{
    protected $mailService;

    public function __construct()
    {
        $this->mailService = new MailService();
    }

    public function send($username, $email, $startTime, $workplace, $confirmUrl, $subject, $data = null)
    {

        // Prepare email content
        $payload = array_merge([
            'name' => $username,
            'start_date' => $startTime ?? date('Y-m-d', strtotime('+7 days')),
            'workplace' => $workplace ?? 'Webman',
            'confirm_url' => $confirmUrl ?? 'https://' . env('SERVER_HOST') . '/confirm?token=',
        ], $data);

        // Render view và loại bỏ các headers không mong muốn
        $body = trim(view('mail-service', $payload));

        // Loại bỏ HTTP headers nếu có
        if (str_starts_with($body, 'HTTP/')) {
            $parts = explode("\r\n\r\n", $body, 2);
            $body = $parts[1] ?? $body;
        }

        return $this->mailService->send($email, $subject, $body);
    }
}
