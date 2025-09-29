<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'email',
        'avatar',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'profile_raw',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'profile_raw' => 'array',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }

        return now()->greaterThanOrEqualTo($this->token_expires_at);
    }

    public static function normalizeProvider(string $provider): string
    {
        return Str::lower($provider);
    }
}
