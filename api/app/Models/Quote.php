<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'client_id',
        'quote_number',
        'issue_date',
        'expiry_date',
        'subtotal',
        'tax_amount',
        'total',
        'status',
        'notes',
        'terms_conditions'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2'
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

    public function quoteItems()
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', '!=', 'accepted')
                    ->where('expiry_date', '<', now());
    }

    // Accessors
    public function getIsExpiredAttribute()
    {
        return $this->status !== 'accepted' && $this->expiry_date < now();
    }

    public function getIsAcceptedAttribute()
    {
        return $this->status === 'accepted';
    }

    public function getCanBeConvertedToInvoiceAttribute()
    {
        return $this->status === 'accepted' && !$this->invoice;
    }
}
