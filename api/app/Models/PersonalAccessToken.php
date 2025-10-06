<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $casts = [
        'abilities' => 'json',
        'meta' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
        'meta',
        'revoked_at',
    ];

    public function revoke(): void
    {
        $this->forceFill([
            'revoked_at' => now(),
        ])->save();
    }

    public function isRevoked(): bool
    {
        return !is_null($this->revoked_at);
    }

    public function rateLimit(): int
    {
        return (int) ($this->meta['rate_limit_per_minute'] ?? config('services.api_tokens.default_rate', 120));
    }

    public function rateLimitDecaySeconds(): int
    {
        return (int) ($this->meta['rate_limit_decay_seconds'] ?? config('services.api_tokens.default_decay', 60));
    }

    public function rateLimiterKey(?string $suffix = null): string
    {
        return trim('api-token:' . $this->id . ($suffix ? ':' . sha1($suffix) : ''));
    }

    public function scopes(): array
    {
        return $this->abilities ?? [];
    }
}
