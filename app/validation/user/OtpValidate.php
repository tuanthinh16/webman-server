<?php

namespace app\validation\user;

use app\validation\CustomRulesNotExists;
use support\Validation;

class OtpValidate
{
    public static function rules(): array
    {
        return [
            'otp'    => ['required', 'string', 'max:6', new CustomRulesNotExists('verify_code', 'otp_code')],
            'email' => 'required|email|max:255'
        ];
    }

    public static function messages(): array
    {
        return [
            'otp.required' => 'OTP là bắt buộc',
            'email.required' => 'Email là bắt buộc',
            'email.max' => 'Email vượt quá kí tự cho phép',
        ];
    }

    // do not delete please :()
    public static function validate(array $data): array
    {

        return Validation::validateConfig($data, self::class);
    }
}
