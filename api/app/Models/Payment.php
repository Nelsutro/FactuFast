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
        'flow_customer_id',
        'amount',
        'subject',
        'email',
        'optional',
        'url_return',
        'url_confirmation',
        'timeout',
        'status',
        'payment_type',
        'flow_order',
        'token',
        'confirmed_at',
        'flow_response',
        'payment_date',
        'payment_method',
        'transaction_id',
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
        'confirmed_at' => 'datetime',
        'amount' => 'decimal:2',
        'raw_gateway_response' => 'array',
        'flow_response' => 'array'
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

    public function flowCustomer()
    {
        return $this->belongsTo(FlowCustomer::class, 'flow_customer_id');
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
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

    public function scopeUnpaid($query)
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
