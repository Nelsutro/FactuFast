<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'type',
        'status',
        'total_rows',
        'processed_rows',
        'success_count',
        'error_count',
        'source_filename',
        'stored_path',
        'error_report_path',
        'started_at',
        'finished_at',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportBatchRow::class);
    }

    public function markProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'finished_at' => now(),
        ]);
    }

    public function markFailed(string $message = null): void
    {
        $payload = $this->meta ?? [];
        if ($message) {
            $payload['error'] = $message;
        }

        $this->update([
            'status' => 'failed',
            'finished_at' => now(),
            'meta' => $payload,
        ]);
    }

    public function incrementEach(array $values): void
    {
        foreach ($values as $column => $amount) {
            $this->increment($column, $amount);
        }
        $this->refresh();
    }
}
