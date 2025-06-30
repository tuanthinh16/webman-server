<?php

namespace support\validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Database\Capsule\Manager as Capsule;
use support\Container;

// Khởi tạo Eloquent Capsule (nếu Webman chưa cấu hình thì phải làm thủ công)
$capsule = new Capsule;
$capsule->addConnection(config('database.connections.mysql'));
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Tạo translator cho Validator
$translator = new Translator(new ArrayLoader(), 'en');

// Tạo Validator Factory
$validatorFactory = new Factory($translator);

// Gán PresenceVerifier cho các rule như `exists`, `unique`
$presenceVerifier = new DatabasePresenceVerifier($capsule->getDatabaseManager());
$validatorFactory->setPresenceVerifier($presenceVerifier);

// Đăng vào DI container
Container::set(Factory::class, $validatorFactory);
