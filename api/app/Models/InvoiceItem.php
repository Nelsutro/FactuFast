<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'amount'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2'
    ];

    // Relaciones
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // Accessors
    public function getTotalAmountAttribute()
    {
        return $this->quantity * $this->unit_price;
    }

    // Mutators - Calcula automáticamente el amount cuando se asignan quantity o unit_price
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($invoiceItem) {
            if ($invoiceItem->quantity && $invoiceItem->unit_price) {
                $invoiceItem->amount = $invoiceItem->quantity * $invoiceItem->unit_price;
            }
        });
    }
}
