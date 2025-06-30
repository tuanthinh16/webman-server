<?php

namespace app\validation\user;

use Illuminate\Validation\Factory;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Database\Capsule\Manager as Capsule;

class UserValidate
{
        public static function validate(array $data)
        {
            $translator = new Translator(new ArrayLoader(), 'en');
            $factory = new Factory($translator);

            $capsule = new Capsule();
            $capsule->addConnection(config('database.connections.mysql')); 
            $capsule->setAsGlobal();
            $capsule->bootEloquent();

            $presenceVerifier = new DatabasePresenceVerifier($capsule->getDatabaseManager());
            $factory->setPresenceVerifier($presenceVerifier);

            $rules = [
                'username' => 'required|string|max:50',
                'password' => 'required|string|min:1',
                'email'    => 'required|email|unique:wa_users,email|max:255',
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

                return [
                    'status' => false,
                    'message' => $flatErrors,
                ];
            }

            return ['status' => true, 'message' => $validation->validated()];
        }

}
