<?php
// app/Model/UserIdentity.php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserIdentity extends Model
{
    protected $table = 'user_identities';

    // Nếu bạn dùng created_at/updated_at
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'provider_id',
        'provider_user_id',
        'credential',
        'refresh_token',
        'expires_at',
        'extra',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'extra'        => 'array',
    ];

    /**
     * User chính
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Provider (local/google/facebook…)
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AuthProvider::class, 'provider_id');
    }
}
