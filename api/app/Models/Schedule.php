<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'frequency',
        'time',
        'is_active',
        'last_run',
        'next_run',
        'description',
        'settings'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_run' => 'datetime',
        'next_run' => 'datetime',
        'settings' => 'array',
        'time' => 'string'
    ];

    /**
     * Relación con Company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope para schedules activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para schedules de una empresa específica
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope para schedules del sistema (sin empresa específica)
     */
    public function scopeSystem($query)
    {
        return $query->whereNull('company_id');
    }

    /**
     * Scope para schedules listos para ejecutar
     */
    public function scopeReadyToRun($query)
    {
        return $query->where('is_active', true)
                    ->where(function($q) {
                        $q->whereNull('next_run')
                          ->orWhere('next_run', '<=', now());
                    });
    }

    /**
     * Marcar como ejecutado y calcular próxima ejecución
     */
    public function markAsRun()
    {
        $this->last_run = now();
        $this->next_run = $this->calculateNextRun();
        $this->save();
    }

    /**
     * Calcular próxima fecha de ejecución según frecuencia
     */
    private function calculateNextRun()
    {
        switch ($this->frequency) {
            case 'immediate':
                return null; // Se ejecuta inmediatamente cuando se activa
            case 'daily':
                return now()->addDay();
            case 'weekly':
                return now()->addWeek();
            case 'monthly':
                return now()->addMonth();
            case 'yearly':
                return now()->addYear();
            default:
                return now()->addDay();
        }
    }
}
