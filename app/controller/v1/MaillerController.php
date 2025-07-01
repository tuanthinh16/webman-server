<?php

namespace app\controller\v1;

use app\services\CofirmMailSender;
use support\Request;

class MaillerController
{
    public function index(Request $request)
    {
        $username = $request->input('username', 'Tuan Thinh');
        $email = $request->input('email', 'dotuanthinh37.work@gmail');
        $subject = $request->input('subject', 'Thư mời nhận việc');
        $workplace = $request->input('workplace', 'Webman');
        $confirmUrl = $request->input('confirm_url', 'https://fb.com');
        $mailService = new CofirmMailSender();
        $result = $mailService->send($username, $email, null, $workplace, $confirmUrl, $subject);
        return json(['status' => 'success', 'message' => 'Email sent successfully', 'result' => $result]);
    }
}
