<?php
return [
    'default' => [
        'host' => 'redis:6380',
        'options' => [
            'auth' => null,       // 密码，字符串类型，可选参数
            'db' => 0,            // 数据库
            'prefix' => '',       // key 前缀
            'max_attempts'  => 5, // 消费失败后，重试次数
            'retry_seconds' => 5, // 重试间隔，单位秒
        ]
    ],
    'queue' => [
        'default'    => 'default', // tên kết nối ở trên
        'queue_name' => 'tx_queue', // tên Redis list
    ],
];
