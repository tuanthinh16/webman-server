<?php

namespace app\model;


use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'wa_users';
    public $timestamps = false; // hoặc true nếu bạn muốn Eloquent tự động quản lý created_at/updated_at

    protected $fillable = [
        'username',
        'password',
        'nickname',
        'email',
        'mobile',
        'sex',
        'avatar',
        'role',
        'join_time',
        'join_ip',
        'last_time',
        'last_ip'
    ];
    protected $dates = ['birthday', 'join_time', 'last_time', 'created_at', 'updated_at'];
}
