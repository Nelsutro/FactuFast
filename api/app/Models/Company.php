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
}
