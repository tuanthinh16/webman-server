<?php

namespace app\model;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $table = 'verify_code';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'created_at',
        'valid_time',
        'otp_code',
        'is_used',
        'hash',
        'type', // 'register', 'login', 'reset_password', etc.
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'valid_time' => 'datetime',
        'is_used'    => 'boolean',
    ];
}
