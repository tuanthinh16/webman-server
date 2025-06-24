<?php

namespace support;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Container\Container;

class Db
{
    public static function init()
    {
        $config = config('database');
        $capsule = new Capsule;

        $capsule->addConnection($config['connections'][$config['default']]);

        $capsule->setEventDispatcher(new Dispatcher(new Container));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        return $capsule;
    }

    public static function connection($name = null)
    {
        return Capsule::connection($name);
    }

    public static function table($table)
    {
        return Capsule::table($table);
    }

    public static function schema()
    {
        return Capsule::schema();
    }
}
