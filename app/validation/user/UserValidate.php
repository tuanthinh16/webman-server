<?php

namespace app\validation\user;

use support\Validation;
use app\validation\CustomRulesNotExists;

class UserValidate
{
    public static function rules(): array
    {
        return [
            'username' => 'required|string|max:50',
            'password' => 'required|string|min:1',
            'email'    => ['required', 'email', 'max:255', new CustomRulesNotExists('wa_users', 'email')],
        ];
    }

    public static function messages(): array
    {
        return [
            'username.required' => 'Username là bắt buộc',
            'email.required'    => 'Email là bắt buộc',
        ];
    }

    // do not delete please :()
    public static function validate(array $data): array
    {
        return Validation::validateConfig($data, self::class);
    }
}
