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
        $position = $request->input('position');
        $salary = $request->input('salary');
        $company = env('BUSINESS_NAME');
        $company_address = env('BUSINESS_ADDRESS');
        $response_deadline = date('Y-m-d', strtotime('+7 days'));
        $hr_phone = $request->input('hr_phone');
        $data = ['position' => $position, 'company' => $company, 'response_deadline' => $response_deadline, 'company_address' => $company_address, 'hr_phone' => $hr_phone, 'salary' => $salary];
        $confirmUrl = $request->input('confirm_url', 'https://fb.com');

        $mailService = new CofirmMailSender();
        $result = $mailService->send($username, $email, null, $workplace, $confirmUrl, $subject, $data);
        return json(['status' => 'success', 'message' => 'Email sent successfully', 'result' => $result]);
    }
}
