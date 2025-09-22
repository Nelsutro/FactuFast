<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tax_id',
        'email',
        'phone',
        'address',
        // Settings
        'currency_code',
        'tax_rate',
        'default_payment_terms',
        'logo_path',
        'send_email_on_invoice',
        'send_email_on_payment',
        'portal_enabled',
    ];

    protected $casts = [
        'tax_rate' => 'decimal:2',
        'send_email_on_invoice' => 'boolean',
        'send_email_on_payment' => 'boolean',
        'portal_enabled' => 'boolean',
    ];

    /**
     * Relación con Users (empleados/usuarios de la empresa)
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Relación con Clients (clientes de la empresa)
     */
    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    /**
     * Relación con Invoices (facturas de la empresa)
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Relación con Quotes (cotizaciones de la empresa)
     */
    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * Relación con Payments (pagos de la empresa)
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNotNull('email');
    }

    public function scopeWithCounts($query)
    {
        return $query->withCount(['users', 'clients', 'invoices', 'quotes']);
    }

    // Accessors
    public function getTotalRevenueAttribute()
    {
        return $this->invoices()->where('status', 'paid')->sum('total');
    }

    public function getActiveClientsCountAttribute()
    {
        return $this->clients()->whereHas('invoices')->count();
    }

    public function getOutstandingAmountAttribute()
    {
        return $this->invoices()->where('status', 'pending')->sum('total');
    }
}
