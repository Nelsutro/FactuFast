<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'company_name',
        'company_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relación con Company
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Verificar si el usuario es admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Verificar si el usuario es cliente (empresa)
     */
    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    /**
     * Relación con Invoices (facturas creadas por el usuario)
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Relación con Quotes (cotizaciones creadas por el usuario)
     */
    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * Relación con Payments (pagos registrados por el usuario)
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeClients($query)
    {
        return $query->where('role', 'client');
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('email_verified_at');
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

    public function getCompanyNameAttribute()
    {
        return $this->company ? $this->company->name : null;
    }
}
