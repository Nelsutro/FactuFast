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
}
