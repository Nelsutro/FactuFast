<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'address',
        'access_token',
        'access_token_expires_at',
        'last_login_at'
    ];

    protected $casts = [
        'access_token_expires_at' => 'datetime',
        'last_login_at' => 'datetime'
    ];

    protected $hidden = [
        'access_token'
    ];

    /**
     * Relación con Company
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relación con Invoices
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Relación con Quotes
     */
    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * Relación con Payments a través de invoices
     */
    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Invoice::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNotNull('email');
    }

    public function scopeWithOutstandingBalance($query)
    {
        return $query->whereHas('invoices', function ($query) {
            $query->where('status', 'pending');
        });
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->name;
    }

    public function getOutstandingBalanceAttribute()
    {
        return $this->invoices()->where('status', 'pending')->sum('total');
    }

    public function getTotalPaidAttribute()
    {
        return $this->invoices()->where('status', 'paid')->sum('total');
    }

    public function getLastInvoiceDateAttribute()
    {
        $lastInvoice = $this->invoices()->latest('issue_date')->first();
        return $lastInvoice ? $lastInvoice->issue_date : null;
    }

    // Métodos para el portal de cliente
    public function generateAccessToken()
    {
        $this->access_token = bin2hex(random_bytes(32));
        $this->access_token_expires_at = now()->addDays(7); // Token válido por 7 días
        $this->save();
        
        return $this->access_token;
    }

    public function isTokenValid($token)
    {
        return $this->access_token === $token && 
               $this->access_token_expires_at && 
               $this->access_token_expires_at->isFuture();
    }

    public function getPendingInvoicesAttribute()
    {
        return $this->invoices()->where('status', 'pending')->get();
    }

    public function getOverdueInvoicesAttribute()
    {
        return $this->invoices()
                    ->where('status', 'pending')
                    ->where('due_date', '<', now())
                    ->get();
    }
}
