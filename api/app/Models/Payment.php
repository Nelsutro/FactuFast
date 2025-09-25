<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'client_id',
        'invoice_id',
        'amount',
        'payment_date',
        'payment_method',
        'transaction_id',
        'status',
        'payment_provider',
        'provider_payment_id',
        'intent_status',
        'paid_at',
        'raw_gateway_response',
        'reference',
        'notes'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'raw_gateway_response' => 'array'
    ];

    // Relaciones
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeGatewayPending($query)
    {
        return $query->whereNull('paid_at')->whereIn('intent_status', ['created','initiated','authorized']);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Accessors
    public function getIsCompletedAttribute()
    {
        return $this->status === 'completed';
    }

    public function getIsPendingAttribute()
    {
        return $this->status === 'pending';
    }

    public function getIsPaidAttribute()
    {
        return $this->status === 'completed' || ($this->paid_at !== null);
    }
}
