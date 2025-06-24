<?php

namespace app\model;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    // Table name
    protected $table = 'orders';

    // Primary key
    protected $primaryKey = 'id';

    // Disable Eloquent timestamps management
    public $timestamps = false;

    // Mass assignable attributes
    protected $fillable = [
        'request_id',
        'user_id',
        'symbol',
        'side',
        'type',
        'time_in_force',
        'price',
        'quantity',
        'timestamp',
        'pre_hash',
        'hash',
        'status',
        'response',
        'volume',
        'leverage',
        'created_at',
    ];

    // Casts for numeric fields
    protected $casts = [
        'price'     => 'decimal:8',
        'quantity'  => 'decimal:8',
        'volume'    => 'decimal:8',
        'leverage'  => 'decimal:2',
        'timestamp' => 'integer',
    ];

    // Hide internal fields if needed
    protected $hidden = [
        'response',
    ];

    // If you want to customize date format for created_at
    protected $dates = [
        'created_at',
    ];
}
