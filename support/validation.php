<?php
namespace support;

use Illuminate\Validation\Factory;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Database\Capsule\Manager as Capsule;

class Validation
{
    public static function validateConfig(array $data, String $className)
    {
        $translator = new Translator(new ArrayLoader(), 'en');
        $factory = new Factory($translator);

        $capsule = new Capsule();
        $capsule->addConnection(config('database.connections.mysql')); 
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $presenceVerifier = new DatabasePresenceVerifier($capsule->getDatabaseManager());
        $factory->setPresenceVerifier($presenceVerifier);

        $rules = $className::rules();
        $messages = $className::messages();

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
