<?php

namespace app\validation\user;

use Illuminate\Validation\Factory;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Foundation\Http\FormRequest;

class UserValidate
{
    public static function validate(array $data)
    {
        $translator = new Translator(new ArrayLoader(), 'en');
        $factory = new Factory($translator);
        $rules = [
            'username' => 'required|string|max:50',
            'password' => 'required|string|min:1',
            'email'    => 'required|email|max:255',
        ];

        $messages = [
            'username.required' => 'Username là bắt buộc',
            'password.required' => 'Password là bắt buộc',
            'email.required'    => 'Email là bắt buộc',
        ];

        $validation = $factory->make($data, $rules, $messages);

        if ($validation->fails()) {
            $errors = $validation->errors()->toArray();
            $flatErrors = [];

            foreach ($errors as $field => $messages) {
                $flatErrors[$field] = $messages[0];
            }

            return  [
                'status' => false,
                'message' => $flatErrors,
            ];
        }

        return ['status' => true, 'message' => $validation->validated()];
    }
}
