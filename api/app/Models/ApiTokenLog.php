<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiTokenLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'personal_access_token_id',
        'user_id',
        'company_id',
        'ip',
        'method',
        'path',
        'status_code',
        'duration_ms',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function token(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class, 'personal_access_token_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
