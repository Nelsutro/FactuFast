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
        'valid_until',
        'amount',
        'status',
        'notes'
    ];

    protected $casts = [
        'valid_until' => 'date',
        'amount' => 'decimal:2'
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

    public function items()
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
                    ->where('valid_until', '<', now());
    }

    // Accessors
    public function getIsExpiredAttribute()
    {
        return $this->status !== 'accepted' && $this->valid_until < now();
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
