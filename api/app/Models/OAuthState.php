<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OAuthState extends Model
{
    use HasFactory;

    protected $table = 'oauth_states';

    protected $fillable = [
        'state',
        'provider',
        'redirect_uri',
        'payload',
        'expires_at',
        'consumed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public static function generateToken(): string
    {
        return hash('sha256', Str::uuid()->toString().microtime(true));
    }

    public function markConsumed(): void
    {
        $this->consumed_at = now();
        $this->save();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->lt(now());
    }
}
