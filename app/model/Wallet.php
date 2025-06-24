<?php

namespace app\model;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    // Table name
    protected $table = 'wallet';

    // Primary key
    protected $primaryKey = 'id';

    // Disable auto-timestamps (we’ll manage them manually)
    public $timestamps = false;

    // Which columns can be mass-assigned
    protected $fillable = [
        'user_id',
        'currency',
        'amount',
        'locked_amount',
        'created_at',
        'updated_at',
    ];

    // Casts for numeric types
    protected $casts = [
        'amount'        => 'decimal:8',
        'locked_amount' => 'decimal:8',
    ];

    // Relationship back to User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
