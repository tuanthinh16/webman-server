<?php

namespace app\validation\user;

use Illuminate\Support\Facades\Validator;

class UserValidate
{

    public static function validateCreate()
    {
        var_dump('UserValidate::validateCreate called');
        exit();
        // $errors = [];
        // if (empty($data['username'])) {
        //     $errors[] = 'Username is required.';
        // }
        // if (empty($data['password'])) {
        //     $errors[] = 'Password is required.';
        // }
        // if (empty($data['email'])) {
        //     $errors[] = 'Email is required.';
        // } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        //     $errors[] = 'Invalid email format.';
        // }

        // return $errors;
    }
}
