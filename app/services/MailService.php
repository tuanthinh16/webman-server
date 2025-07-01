<?php

namespace app\services;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    public static function send($to, $subject, $body)
    {
        $mail = new PHPMailer(true);

        try {
            // Cấu hình SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = env('GOOGLE_MAIL');
            $mail->Password   = env('GOOGLE_PASSWORD_APP');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Thiết lập encoding
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

            // Thiết lập người gửi/nhận
            $mail->setFrom(env('GOOGLE_MAIL'), env('BUSINESS_NAME'));
            $mail->addAddress($to);

            // Thiết lập nội dung
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            // Gửi email
            if (!$mail->send()) {
                throw new Exception($mail->ErrorInfo);
            }
            return true;
        } catch (Exception $e) {
            error_log('Mail error: ' . $e->getMessage());
            return false;
        }
    }
}
