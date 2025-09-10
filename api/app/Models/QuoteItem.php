<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
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
    public function quote()
    {
        return $this->belongsTo(Quote::class);
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
        
        static::saving(function ($quoteItem) {
            $quoteItem->amount = $quoteItem->quantity * $quoteItem->unit_price;
        });
    }
}
