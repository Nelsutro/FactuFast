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

    protected $appends = [
        'alert_level',
        'has_errors',
        'duration_seconds',
        'summary_message',
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

    public function markProcessing(?int $attempt = null): void
    {
        $payload = [
            'status' => 'processing',
        ];

        if (is_null($this->started_at)) {
            $payload['started_at'] = now();
        }

        if (!is_null($attempt)) {
            $meta = $this->meta ?? [];
            $meta['attempts'] = $attempt;
            $meta['last_attempt_at'] = now()->toISOString();
            $payload['meta'] = $meta;
        }

        $this->update($payload);
    }

    public function markCompleted(): void
    {
        $meta = $this->meta ?? [];
        unset($meta['error']);

        $now = now();
        if ($this->started_at) {
            $meta['last_duration_ms'] = $this->started_at->diffInMilliseconds($now);
        }
        $meta['last_success_at'] = $now->toISOString();

        $this->update([
            'status' => 'completed',
            'finished_at' => $now,
            'meta' => $meta,
        ]);
    }

    public function markFailed(string $message = null): void
    {
        $payload = $this->meta ?? [];
        if ($message) {
            $payload['error'] = $message;
        }

        $now = now();
        if ($this->started_at) {
            $payload['last_duration_ms'] = $this->started_at->diffInMilliseconds($now);
        }
        $payload['last_error_at'] = $now->toISOString();

        $this->update([
            'status' => 'failed',
            'finished_at' => $now,
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

    public function syncCounters(): void
    {
        $counts = $this->rows()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $processed = array_sum($counts->all());

        $this->forceFill([
            'processed_rows' => $processed,
            'success_count' => (int) ($counts['success'] ?? 0),
            'error_count' => (int) ($counts['error'] ?? 0),
        ])->save();
    }

    public function getAlertLevelAttribute(): string
    {
        if ($this->status === 'failed') {
            return 'error';
        }

        if ($this->status === 'completed') {
            return $this->error_count > 0 ? 'warning' : 'success';
        }

        if (in_array($this->status, ['processing', 'pending'], true)) {
            return 'info';
        }

        return 'info';
    }

    public function getHasErrorsAttribute(): bool
    {
        return (int) $this->error_count > 0;
    }

    public function getDurationSecondsAttribute(): ?int
    {
        if (!$this->started_at || !$this->finished_at) {
            return null;
        }

        return $this->finished_at->diffInSeconds($this->started_at);
    }

    public function getSummaryMessageAttribute(): string
    {
        return match ($this->status) {
            'failed' => 'Importación fallida. Revisa los detalles y vuelve a intentarlo.',
            'completed' => $this->error_count > 0
                ? sprintf('Importación completada con %d errores detectados.', (int) $this->error_count)
                : 'Importación completada exitosamente.',
            'processing' => 'Importación en proceso, monitoreando el avance...',
            default => 'Importación pendiente de procesamiento.',
        };
    }
}
