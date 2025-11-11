<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlowCustomer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'flow_customer_id',
        'external_id',
        'name',
        'email',
        'has_registered_card',
        'card_last_digits',
        'card_brand',
        'card_registered_at',
        'status',
        'flow_response',
        'registration_response'
    ];

    protected $casts = [
        'has_registered_card' => 'boolean',
        'card_registered_at' => 'datetime',
        'flow_response' => 'array',
        'registration_response' => 'array'
    ];

    /**
     * Relación con la empresa
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relación con los pagos
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'flow_customer_id');
    }

    /**
     * Scope para clientes activos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope para clientes con tarjeta registrada
     */
    public function scopeWithCard($query)
    {
        return $query->where('has_registered_card', true);
    }

    /**
     * Verificar si el cliente tiene una tarjeta registrada
     */
    public function hasRegisteredCard(): bool
    {
        return $this->has_registered_card;
    }

    /**
     * Marcar cliente como con tarjeta registrada
     */
    public function markCardRegistered(array $cardData): void
    {
        $this->update([
            'has_registered_card' => true,
            'card_last_digits' => $cardData['last_digits'] ?? null,
            'card_brand' => $cardData['brand'] ?? null,
            'card_registered_at' => now(),
            'registration_response' => $cardData
        ]);
    }

    /**
     * Remover tarjeta registrada
     */
    public function removeCard(): void
    {
        $this->update([
            'has_registered_card' => false,
            'card_last_digits' => null,
            'card_brand' => null,
            'card_registered_at' => null,
            'registration_response' => null
        ]);
    }
}