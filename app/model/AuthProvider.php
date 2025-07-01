<?php
// app/Model/AuthProvider.php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuthProvider extends Model
{
    // Nếu bạn đặt tên file/table khác
    protected $table = 'auth_providers';

    public $timestamps = false; // không có created_at/updated_at

    protected $fillable = [
        'slug',
        'display_name',
    ];

    /**
     * Các user identities thuộc provider này
     */
    public function identities(): HasMany
    {
        return $this->hasMany(UserIdentity::class, 'provider_id');
    }
    public static function getIdBySlug(string $slug)
    {
        return self::where('slug', $slug)->value('id');
    }
}
